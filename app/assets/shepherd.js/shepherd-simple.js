/**
 * Shepherd.js Tour Guide - Implementación simple para JavaScript vanilla
 * Adaptado para proyectos PHP sin dependencias
 */

class ShepherdTour {
    constructor(options = {}) {
        this.options = {
            useModalOverlay: true,
            defaultStepOptions: {
                scrollTo: true,
                cancelIcon: {
                    enabled: true
                },
                ...options.defaultStepOptions
            },
            ...options
        };
        this.steps = [];
        this.currentStepIndex = 0;
        this.isActive = false;
        this.overlay = null;
        this.tooltip = null;
    }

    addStep(stepOptions) {
        this.steps.push({
            id: stepOptions.id || `step-${this.steps.length}`,
            title: stepOptions.title || '',
            text: stepOptions.text || '',
            attachTo: stepOptions.attachTo || null,
            buttons: stepOptions.buttons || [],
            classes: stepOptions.classes || '',
            when: stepOptions.when || {},
            scrollTo: stepOptions.scrollTo !== false,
            ...stepOptions
        });
        return this;
    }

    start() {
        if (this.steps.length === 0) return;
        
        this.isActive = true;
        this.currentStepIndex = 0;
        this.createOverlay();
        this.showStep(0);
        return this;
    }

    next() {
        if (this.currentStepIndex < this.steps.length - 1) {
            this.currentStepIndex++;
            this.showStep(this.currentStepIndex);
        }
        return this;
    }

    back() {
        if (this.currentStepIndex > 0) {
            this.currentStepIndex--;
            this.showStep(this.currentStepIndex);
        }
        return this;
    }

    complete() {
        this.isActive = false;
        this.hideTooltip();
        this.removeOverlay();
        if (this.options.onComplete) {
            this.options.onComplete();
        }
        return this;
    }

    cancel() {
        this.isActive = false;
        this.hideTooltip();
        this.removeOverlay();
        if (this.options.onCancel) {
            this.options.onCancel();
        }
        return this;
    }

    showStep(index) {
        if (index < 0 || index >= this.steps.length) return;
        
        const step = this.steps[index];
        this.hideTooltip();
        
        // Ejecutar callback when.show si existe
        if (step.when && step.when.show) {
            step.when.show();
        }
        
        // Scroll al elemento si es necesario
        if (step.scrollTo && step.attachTo && step.attachTo.element) {
            const element = typeof step.attachTo.element === 'string' 
                ? document.querySelector(step.attachTo.element)
                : step.attachTo.element;
            
            if (element) {
                // Verificar si el elemento está visible en el viewport
                const elementRect = element.getBoundingClientRect();
                const isVisible = (
                    elementRect.top >= 0 &&
                    elementRect.left >= 0 &&
                    elementRect.bottom <= window.innerHeight &&
                    elementRect.right <= window.innerWidth
                );
                
                if (!isVisible) {
                    element.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center',
                        inline: 'center'
                    });
                }
            }
        }
        
        setTimeout(() => {
            this.createTooltip(step);
        }, 300);
    }

    createOverlay() {
        if (!this.options.useModalOverlay) return;
        
        this.overlay = document.createElement('div');
        this.overlay.className = 'shepherd-overlay';
        this.overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9998;
            transition: opacity 0.3s ease;
        `;
        
        this.overlay.addEventListener('click', () => this.cancel());
        document.body.appendChild(this.overlay);
    }

    removeOverlay() {
        if (this.overlay) {
            this.overlay.remove();
            this.overlay = null;
        }
    }

    createTooltip(step) {
        this.tooltip = document.createElement('div');
        this.tooltip.className = `shepherd-tooltip ${step.classes}`;
        this.tooltip.style.cssText = `
            position: fixed;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            padding: 0;
            z-index: 9999;
            max-width: 400px;
            min-width: 250px;
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.3s ease;
        `;
        
        // Header
        const header = document.createElement('div');
        header.className = 'shepherd-header';
        header.style.cssText = `
            padding: 16px 20px 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        `;
        
        if (step.title) {
            const title = document.createElement('h3');
            title.className = 'shepherd-title';
            title.textContent = step.title;
            title.style.cssText = `
                margin: 0;
                font-size: 16px;
                font-weight: 600;
                color: #333;
            `;
            header.appendChild(title);
        }
        
        // Cancel button
        if (this.options.defaultStepOptions.cancelIcon.enabled) {
            const cancelBtn = document.createElement('button');
            cancelBtn.className = 'shepherd-cancel-icon';
            cancelBtn.innerHTML = '×';
            cancelBtn.style.cssText = `
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #999;
                padding: 0;
                margin-left: 10px;
            `;
            cancelBtn.addEventListener('click', () => this.cancel());
            header.appendChild(cancelBtn);
        }
        
        this.tooltip.appendChild(header);
        
        // Content
        const content = document.createElement('div');
        content.className = 'shepherd-text';
        content.style.cssText = `
            padding: 12px 20px 16px 20px;
            color: #666;
            line-height: 1.5;
        `;
        content.innerHTML = step.text;
        this.tooltip.appendChild(content);
        
        // Footer with buttons
        if (step.buttons && step.buttons.length > 0) {
            const footer = document.createElement('div');
            footer.className = 'shepherd-footer';
            footer.style.cssText = `
                padding: 0 20px 16px 20px;
                display: flex;
                justify-content: flex-end;
                gap: 8px;
            `;
            
            step.buttons.forEach(buttonConfig => {
                const button = document.createElement('button');
                button.textContent = buttonConfig.text;
                button.className = buttonConfig.classes || '';
                button.style.cssText = `
                    padding: 8px 16px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    background: white;
                    cursor: pointer;
                    font-size: 14px;
                    transition: all 0.2s ease;
                `;
                
                if (buttonConfig.classes && buttonConfig.classes.includes('shepherd-button-primary')) {
                    button.style.cssText += `
                        background: #007bff;
                        color: white;
                        border-color: #007bff;
                    `;
                }
                
                button.addEventListener('click', () => {
                    if (buttonConfig.action) {
                        buttonConfig.action.call(this);
                    }
                });
                
                footer.appendChild(button);
            });
            
            this.tooltip.appendChild(footer);
        }
        
        document.body.appendChild(this.tooltip);
        this.positionTooltip(step);
        
        // Animar la aparición del tooltip
        requestAnimationFrame(() => {
            this.tooltip.style.opacity = '1';
            this.tooltip.style.transform = 'scale(1)';
        });
    }

    positionTooltip(step) {
        if (!step.attachTo || !step.attachTo.element) {
            // Centrar en la pantalla
            this.tooltip.style.position = 'fixed';
            this.tooltip.style.left = '50%';
            this.tooltip.style.top = '50%';
            this.tooltip.style.transform = 'translate(-50%, -50%)';
            this.tooltip.style.zIndex = '9999';
            return;
        }
        
        const element = typeof step.attachTo.element === 'string' 
            ? document.querySelector(step.attachTo.element)
            : step.attachTo.element;
            
        if (!element) {
            // Si no encuentra el elemento, centrar
            this.tooltip.style.position = 'fixed';
            this.tooltip.style.left = '50%';
            this.tooltip.style.top = '50%';
            this.tooltip.style.transform = 'translate(-50%, -50%)';
            this.tooltip.style.zIndex = '9999';
            return;
        }
        
        const elementRect = element.getBoundingClientRect();
        const tooltipRect = this.tooltip.getBoundingClientRect();
        const on = step.attachTo.on || 'bottom';
        
        let left, top;
        
        // Usar posición fija para evitar problemas con scroll
        this.tooltip.style.position = 'fixed';
        this.tooltip.style.zIndex = '9999';
        
        switch (on) {
            case 'top':
                left = elementRect.left + (elementRect.width / 2) - (tooltipRect.width / 2);
                top = elementRect.top - tooltipRect.height - 10;
                break;
            case 'bottom':
                left = elementRect.left + (elementRect.width / 2) - (tooltipRect.width / 2);
                top = elementRect.bottom + 10;
                break;
            case 'left':
                left = elementRect.left - tooltipRect.width - 10;
                top = elementRect.top + (elementRect.height / 2) - (tooltipRect.height / 2);
                break;
            case 'right':
                left = elementRect.right + 10;
                top = elementRect.top + (elementRect.height / 2) - (tooltipRect.height / 2);
                break;
            default:
                left = elementRect.left + (elementRect.width / 2) - (tooltipRect.width / 2);
                top = elementRect.bottom + 10;
        }
        
        // Ajustar si se sale de la pantalla
        const padding = 10;
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        
        if (left < padding) {
            left = padding;
        }
        if (left + tooltipRect.width > viewportWidth - padding) {
            left = viewportWidth - tooltipRect.width - padding;
        }
        if (top < padding) {
            top = padding;
        }
        if (top + tooltipRect.height > viewportHeight - padding) {
            top = viewportHeight - tooltipRect.height - padding;
        }
        
        this.tooltip.style.left = left + 'px';
        this.tooltip.style.top = top + 'px';
        this.tooltip.style.transform = 'none';
        
        // Highlight del elemento
        if (this.options.useModalOverlay) {
            element.style.position = 'relative';
            element.style.zIndex = '9999';
            element.style.boxShadow = '0 0 0 4px rgba(22, 163, 74, 0.3)';
            element.style.borderRadius = '4px';
        }
    }

    hideTooltip() {
        if (this.tooltip) {
            this.tooltip.remove();
            this.tooltip = null;
        }
        
        // Remover highlights y limpiar estilos
        document.querySelectorAll('[style*="z-index: 9999"]').forEach(el => {
            // Limpiar los estilos agregados por el tour
            el.style.removeProperty('z-index');
            el.style.removeProperty('position');
            el.style.removeProperty('box-shadow');
            el.style.removeProperty('border-radius');
            
            // Si el elemento no tiene más estilos, remover el atributo style
            if (!el.getAttribute('style') || el.getAttribute('style').trim() === '') {
                el.removeAttribute('style');
            }
        });
    }
}

// Crear instancia global para compatibilidad
window.Shepherd = {
    Tour: ShepherdTour
};

// Export para módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { Tour: ShepherdTour };
}
