const registerAdminGuideShell = () => {
    const Alpine = window.Alpine;

    if (! Alpine || window.__adhAdminGuideShellRegistered) {
        return;
    }

    window.__adhAdminGuideShellRegistered = true;

    Alpine.data('adminGuideShell', (config = {}) => ({
        catalog: config.catalog ?? [],
        activeGuideKey: config.currentGuideKey ?? config.catalog?.[0]?.guide_key ?? null,
        currentGuideKey: config.currentGuideKey ?? null,
        progressMap: config.progressMap ?? {},
        updateUrl: config.updateUrl ?? null,
        csrfToken: config.csrfToken ?? null,
        panelOpen: false,
        tourActive: false,
        currentStepIndex: 0,
        highlightStyle: '',
        coachmarkStyle: '',
        activeTargetVisible: false,

        init() {
            window.addEventListener('adh-admin-guide:open', () => {
                this.panelOpen = true;
                this.activeGuideKey = this.activeGuideKey ?? this.currentGuideKey ?? this.catalog?.[0]?.guide_key ?? null;
            });

            window.addEventListener('adh-admin-guide:start', (event) => {
                const key = event.detail?.key ?? this.activeGuideKey ?? this.currentGuideKey;

                if (key) {
                    this.startGuide(key);
                }
            });

            window.addEventListener('resize', () => {
                if (this.tourActive) {
                    this.$nextTick(() => this.positionTour());
                }
            });

            window.addEventListener('scroll', () => {
                if (this.tourActive) {
                    this.positionTour();
                }
            }, true);
        },

        get currentGuide() {
            return this.catalog.find((guide) => guide.guide_key === this.activeGuideKey) ?? null;
        },

        closePanel() {
            this.panelOpen = false;
        },

        selectGuide(key) {
            this.activeGuideKey = key;
            this.panelOpen = true;
        },

        progressLabel(key) {
            const progress = this.progressMap[key];

            if (! progress) {
                return '';
            }

            return this.statusLabel(progress.status);
        },

        currentGuideStatusLabel() {
            if (! this.currentGuide) {
                return '';
            }

            return this.progressLabel(this.currentGuide.guide_key);
        },

        statusLabel(status) {
            switch (status) {
                case 'completed':
                    return 'Tamamlandi';
                case 'in_progress':
                    return 'Devam ediyor';
                case 'dismissed':
                    return 'Simdilik kapatildi';
                default:
                    return '';
            }
        },

        startGuide(key) {
            this.activeGuideKey = key;
            this.panelOpen = false;
            this.tourActive = true;

            const progress = this.progressMap[key];
            const maxIndex = Math.max((this.currentGuide?.coachmarks?.length ?? 1) - 1, 0);
            this.currentStepIndex = Math.min(progress?.last_step_index ?? 0, maxIndex);

            this.persistProgress('in_progress');

            this.$nextTick(() => {
                this.positionTour(true);
            });
        },

        activeCoachmark() {
            if (! this.currentGuide) {
                return null;
            }

            const coachmarks = this.currentGuide.coachmarks ?? [];

            if (coachmarks.length === 0) {
                return null;
            }

            return coachmarks[this.currentStepIndex] ?? coachmarks[0];
        },

        tourStepLabel() {
            const coachmarks = this.currentGuide?.coachmarks ?? [];
            const total = Math.max(coachmarks.length, 1);

            return `Adim ${Math.min(this.currentStepIndex + 1, total)} / ${total}`;
        },

        isLastStep() {
            const coachmarks = this.currentGuide?.coachmarks ?? [];

            return this.currentStepIndex >= Math.max(coachmarks.length - 1, 0);
        },

        previousStep() {
            if (this.currentStepIndex === 0) {
                return;
            }

            this.currentStepIndex -= 1;
            this.persistProgress('in_progress');
            this.$nextTick(() => this.positionTour(true));
        },

        advanceTour() {
            if (this.isLastStep()) {
                this.completeTour();

                return;
            }

            this.currentStepIndex += 1;
            this.persistProgress('in_progress');
            this.$nextTick(() => this.positionTour(true));
        },

        completeTour() {
            this.persistProgress('completed');
            this.resetTourState();
        },

        dismissTour() {
            this.persistProgress('dismissed');
            this.resetTourState();
        },

        markDismissed(key) {
            this.activeGuideKey = key;
            this.persistProgress('dismissed');
        },

        resetTourState() {
            this.tourActive = false;
            this.activeTargetVisible = false;
            this.highlightStyle = '';
            this.coachmarkStyle = '';
        },

        resolveTarget(step) {
            if (! step) {
                return null;
            }

            if (step.anchor) {
                const target = document.querySelector(`[data-tour-anchor="${step.anchor}"]`);

                if (target) {
                    return target;
                }
            }

            if (step.selector) {
                return document.querySelector(step.selector);
            }

            return null;
        },

        positionTour(shouldScroll = false) {
            const step = this.activeCoachmark();
            const target = this.resolveTarget(step);

            if (! target) {
                this.activeTargetVisible = false;
                this.highlightStyle = 'display:none;';
                this.coachmarkStyle = 'position: relative; margin-top: 8vh;';

                return;
            }

            if (shouldScroll) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                    inline: 'nearest',
                });
            }

            window.requestAnimationFrame(() => {
                const rect = target.getBoundingClientRect();
                const padding = 10;
                const viewportWidth = window.innerWidth;
                const viewportHeight = window.innerHeight;
                const width = Math.max(rect.width + padding * 2, 96);
                const height = Math.max(rect.height + padding * 2, 72);
                const top = Math.max(rect.top - padding, 16);
                const left = Math.max(rect.left - padding, 16);
                const popoverWidth = Math.min(420, viewportWidth - 32);
                const desiredLeft = Math.min(Math.max(left, 16), viewportWidth - popoverWidth - 16);
                const belowTop = rect.bottom + 22;
                const aboveTop = rect.top - 220;
                const coachmarkTop = belowTop + 220 <= viewportHeight ? belowTop : Math.max(16, aboveTop);

                this.activeTargetVisible = true;
                this.highlightStyle = `top:${top}px;left:${left}px;width:${width}px;height:${height}px;`;
                this.coachmarkStyle = `position: fixed; top:${coachmarkTop}px; left:${desiredLeft}px;`;
            });
        },

        async persistProgress(status) {
            if (! this.currentGuide || ! this.updateUrl || ! this.csrfToken) {
                return;
            }

            const payload = {
                guide_key: this.currentGuide.guide_key,
                status,
                last_step_index: this.currentStepIndex,
                meta: {
                    path: window.location.pathname,
                },
            };

            try {
                const response = await fetch(this.updateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                });

                if (! response.ok) {
                    return;
                }

                const data = await response.json();

                if (data?.progress?.guide_key) {
                    this.progressMap[data.progress.guide_key] = data.progress;
                }
            } catch (error) {
                console.warn('Admin guide progress could not be stored.', error);
            }
        },
    }));
};

if (window.Alpine) {
    registerAdminGuideShell();
}

document.addEventListener('alpine:init', registerAdminGuideShell);
