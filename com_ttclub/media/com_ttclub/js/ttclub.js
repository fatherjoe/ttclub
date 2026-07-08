/**
 * @package     com_ttclub
 * @copyright   (C) 2025 Fatherjoe. All rights reserved.
 * @license     GNU General Public License version 2 or later
 *
 * Frontend JavaScript for Table Tennis Club Manager.
 */

'use strict';

((document) => {
    /**
     * Simple form validation helper for frontend forms.
     * Adds 'is-invalid' class to empty required fields on submit.
     */
    const initFormValidation = () => {
        const forms = document.querySelectorAll('.com-ttclub-form[data-validate]');

        forms.forEach((form) => {
            form.addEventListener('submit', (event) => {
                let isValid = true;
                const requiredFields = form.querySelectorAll('[required]');

                requiredFields.forEach((field) => {
                    field.classList.remove('is-invalid');

                    if (!field.value.trim()) {
                        field.classList.add('is-invalid');
                        isValid = false;
                    }
                });

                if (!isValid) {
                    event.preventDefault();
                }
            });
        });
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFormValidation);
    } else {
        initFormValidation();
    }
})(document);
