(function () {
    const copyBtn = document.querySelector('[data-auth-referral-copy]');
    const linkInput = document.getElementById('auth-referral-link');

    if (copyBtn && linkInput) {
        copyBtn.addEventListener('click', function () {
            const text = linkInput.value;
            const flashCopied = function () {
                const original = copyBtn.textContent;
                copyBtn.textContent = 'Copied';
                window.setTimeout(function () {
                    copyBtn.textContent = original;
                }, 1600);
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(flashCopied).catch(function () {
                    linkInput.focus();
                    linkInput.select();
                    try {
                        document.execCommand('copy');
                        flashCopied();
                    } catch (e) {
                        /* ignore */
                    }
                });
                return;
            }

            linkInput.focus();
            linkInput.select();
            try {
                document.execCommand('copy');
                flashCopied();
            } catch (e) {
                /* ignore */
            }
        });
    }

    const form = document.querySelector('[data-otp-form]');
    const grid = document.querySelector('[data-otp-grid]');
    if (!form || !grid) {
        return;
    }

    const cells = Array.from(grid.querySelectorAll('[data-otp-cell]'));
    const hiddenCode = document.getElementById('auth-code');

    const syncCode = () => {
        if (!hiddenCode) {
            return;
        }
        hiddenCode.value = cells.map((cell) => cell.value.replace(/\D/g, '')).join('');
    };

    const focusCell = (index) => {
        const cell = cells[index];
        if (cell) {
            cell.focus();
            cell.select();
        }
    };

    cells.forEach((cell, index) => {
        cell.addEventListener('input', () => {
            cell.value = cell.value.replace(/\D/g, '').slice(-1);
            syncCode();

            if (cell.value && index < cells.length - 1) {
                focusCell(index + 1);
            }
        });

        cell.addEventListener('keydown', (event) => {
            if (event.key === 'Backspace' && !cell.value && index > 0) {
                focusCell(index - 1);
            }

            if (event.key === 'ArrowLeft' && index > 0) {
                event.preventDefault();
                focusCell(index - 1);
            }

            if (event.key === 'ArrowRight' && index < cells.length - 1) {
                event.preventDefault();
                focusCell(index + 1);
            }
        });

        cell.addEventListener('paste', (event) => {
            event.preventDefault();
            const pasted = (event.clipboardData?.getData('text') ?? '').replace(/\D/g, '');
            if (!pasted) {
                return;
            }

            pasted.split('').slice(0, cells.length).forEach((digit, offset) => {
                cells[offset].value = digit;
            });
            syncCode();
            focusCell(Math.min(pasted.length, cells.length - 1));
        });
    });

    form.addEventListener('submit', (event) => {
        syncCode();
        if (!hiddenCode || hiddenCode.value.length !== cells.length) {
            event.preventDefault();
            focusCell(0);
        }
    });
})();
