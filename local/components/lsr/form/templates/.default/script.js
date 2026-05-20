(() => {
    'use strict';

    class Autocomplete {
        constructor({ inputId, hiddenId, listId, label, fetchUrl, canSearch, onSelect, onClear, debounceMs = 250 }) {
            this.input = BX(inputId);
            this.hidden = BX(hiddenId);
            this.list = BX(listId);
            this.label = label;
            this.fetchUrl = fetchUrl;
            this.canSearch = canSearch || (() => true);
            this.onSelect = onSelect || (() => {});
            this.onClear = onClear || (() => {});

            this.items = [];
            this.activeIdx = -1;
            this.requestSeq = 0;

            this.searchDebounced = BX.debounce(this.search.bind(this), debounceMs);
            this.bind();
        }

        bind() {
            BX.bind(this.input, 'input', () => {
                if (this.hidden.value) {
                    this.hidden.value = '';
                    this.onClear();
                }
                this.searchDebounced(this.input.value.trim());
            });

            BX.bind(this.input, 'focus', () => {
                if (!this.input.disabled && !this.hidden.value) {
                    this.searchDebounced(this.input.value.trim());
                }
            });

            BX.bind(this.input, 'keydown', (e) => this.onKey(e));

            BX.bind(this.list, 'mousedown', (e) => {
                const li = e.target.closest('[data-idx]');
                if (!li) return;
                e.preventDefault();
                this.pick(parseInt(li.dataset.idx, 10));
            });

            BX.bind(document, 'click', (e) => {
                if (!this.input.contains(e.target) && !this.list.contains(e.target)) {
                    this.close();
                }
            });
        }

        onKey(e) {
            if (this.list.hidden) return;
            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    this.activeIdx = Math.min(this.activeIdx + 1, this.items.length - 1);
                    this.render();
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    this.activeIdx = Math.max(this.activeIdx - 1, 0);
                    this.render();
                    break;
                case 'Enter':
                    if (this.activeIdx >= 0) {
                        e.preventDefault();
                        this.pick(this.activeIdx);
                    }
                    break;
                case 'Escape':
                    this.close();
                    break;
            }
        }

        render() {
            BX.cleanNode(this.list);
            if (!this.items.length) {
                this.list.appendChild(BX.create('li', {
                    props: { className: 'lsr-form__suggest-empty' },
                    text: 'ничего не найдено',
                }));
                this.list.hidden = false;
                return;
            }
            this.items.forEach((item, i) => {
                this.list.appendChild(BX.create('li', {
                    attrs: { 'data-idx': i },
                    props: { className: `lsr-form__suggest-item${i === this.activeIdx ? ' is-active' : ''}` },
                    text: String(this.label(item)),
                }));
            });
            this.list.hidden = false;
        }

        close() {
            BX.cleanNode(this.list);
            this.list.hidden = true;
            this.activeIdx = -1;
        }

        pick(idx) {
            const item = this.items[idx];
            if (!item) return;
            this.input.value = this.label(item);
            this.hidden.value = item.ID;
            this.close();
            this.onSelect(item);
        }

        search(q) {
            if (!this.canSearch()) {
                this.close();
                return;
            }
            const seq = ++this.requestSeq;
            BX.ajax({
                url: this.fetchUrl(q),
                method: 'GET',
                dataType: 'json',
                onsuccess: (data) => {
                    if (seq !== this.requestSeq) return;
                    this.items = (data && data.items) || [];
                    this.activeIdx = -1;
                    this.render();
                },
                onfailure: () => {
                    if (seq !== this.requestSeq) return;
                    this.items = [];
                    BX.cleanNode(this.list);
                    this.list.appendChild(BX.create('li', {
                        props: { className: 'lsr-form__suggest-empty' },
                        text: 'ошибка загрузки',
                    }));
                    this.list.hidden = false;
                },
            });
        }

        reset(placeholder, disabled) {
            this.input.value = '';
            this.hidden.value = '';
            if (typeof placeholder === 'string') this.input.placeholder = placeholder;
            this.input.disabled = !!disabled;
            this.close();
        }

        getValue() {
            return this.hidden.value;
        }
    }

    BX.ready(() => {
        const cfg = window.LSR_FORM_CONFIG || {};
        const form = BX('lsr-form');
        if (!form) return;

        const messageBox = BX('lsr-form-message');
        const submitBtn = form.querySelector('.lsr-form__submit');
        const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        const showError = (field, text) => {
            const el = form.querySelector(`.lsr-form__error[data-field="${field}"]`);
            if (el) el.textContent = text || '';
        };

        const clearErrors = () => {
            form.querySelectorAll('.lsr-form__error').forEach((el) => { el.textContent = ''; });
            messageBox.textContent = '';
            messageBox.className = 'lsr-form__message';
        };

        const showMessage = (text, kind = 'info') => {
            messageBox.textContent = text;
            messageBox.className = `lsr-form__message lsr-form__message--${kind}`;
        };

        const normalizePhone = (v) => (v || '').replace(/\D+/g, '');

        let aptAC;

        const houseAC = new Autocomplete({
            inputId: 'lsr-house-input',
            hiddenId: 'lsr-house',
            listId: 'lsr-house-suggest',
            label: (h) => h.NAME,
            fetchUrl: (q) => `${cfg.ajaxUrl}?action=houses_search&q=${encodeURIComponent(q)}`,
            onSelect: () => aptAC.reset('— выберите квартиру —', false),
            onClear: () => aptAC.reset('Сначала выберите дом', true),
        });

        aptAC = new Autocomplete({
            inputId: 'lsr-apartment-input',
            hiddenId: 'lsr-apartment',
            listId: 'lsr-apartment-suggest',
            label: (a) => a.NUMBER,
            canSearch: () => !!houseAC.getValue(),
            fetchUrl: (q) => `${cfg.ajaxUrl}?action=apartments`
                + `&house_id=${encodeURIComponent(houseAC.getValue())}`
                + `&q=${encodeURIComponent(q)}`,
        });

        const validateClient = () => {
            clearErrors();
            let ok = true;

            const name = form.elements['name'].value.trim();
            if (!name) { showError('name', 'Укажите имя'); ok = false; }

            const email = form.elements['email'].value.trim();
            if (!EMAIL_RE.test(email)) {
                showError('email', 'Некорректная почта'); ok = false;
            }


            const phoneDigits = normalizePhone(form.elements['phone'].value);
            if (phoneDigits.length < 10 || phoneDigits.length > 15) {
                showError('phone', 'Некорректный телефон');
                ok = false;
            }

            if (!parseInt(houseAC.getValue(), 10)) {
                showError('house_id', 'Выберите дом');
                ok = false;
            }
            if (!parseInt(aptAC.getValue(), 10)) {
                showError('apartment_id', 'Выберите квартиру');
                ok = false;
            }

            return ok;
        };

        BX.bind(form, 'submit', (e) => {
            e.preventDefault();
            if (!validateClient()) return;

            submitBtn.disabled = true;

            const data = Object.fromEntries(new FormData(form));

            BX.ajax({
                url: cfg.ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data,
                onsuccess: (data) => {
                    data = data || {};
                    if (data.status === 'ok') {
                        showMessage(data.message || 'ок', 'success');
                        form.reset();
                        houseAC.reset('Начните вводить название дома', false);
                        aptAC.reset('Сначала выберите дом', true);
                        return;
                    }
                    switch (data.status) {
                        case 'email_duplicate': showError('email', data.message); break;
                        case 'phone_duplicate': showError('phone', data.message); break;
                        case 'apartment_not_free':
                        case 'apartment_not_found':
                            showError('apartment_id', data.message);
                            break;
                        default:
                            showMessage(data.message || 'Ошибка отправки', 'error');
                    }
                },
                onfailure: () => showMessage('Ошибка сети, попробуйте ещё раз', 'error'),
                oncomplete: () => { submitBtn.disabled = false; },
            });
        });
    });
})();
