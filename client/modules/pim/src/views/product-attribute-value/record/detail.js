/*
 * Pim
 * Free Extension
 * Copyright (c) TreoLabs GmbH
 * Copyright (c) Kenner Soft Service GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

Espo.define('pim:views/product-attribute-value/record/detail', 'views/record/detail',
    Dep => Dep.extend({

        pipelines: {
            updateModelDefs: ['clientDefs', 'ProductAttributeValue', 'updateModelDefs'],
            changeFieldsReadOnlyStatus: ['clientDefs', 'ProductAttributeValue', 'changeFieldsReadOnlyStatus']
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.handleValueModelDefsUpdating();
        },

        handleValueModelDefsUpdating() {
            this.updateModelDefs();
            this.listenTo(this.model, 'change:attributeId', () => {
                this.updateModelDefs();
                if (this.model.get('attributeId')) {
                    const inputLanguageList = this.getConfig().get('inputLanguageList') || [];

                    if (this.getConfig().get('isMultilangActive') && inputLanguageList.length) {
                        const valuesKeysList = ['value', ...inputLanguageList.map(lang => {
                            return lang.split('_').reduce((prev, curr) => prev + Espo.Utils.upperCaseFirst(curr.toLocaleLowerCase()), 'value');
                        })];

                        valuesKeysList.forEach(value => {
                            this.model.set({[value]: null}, { silent: true });
                        });
                    }

                    this.clearView('middle');
                    this.gridLayout = null;
                    this.createMiddleView(() => this.reRender());
                }
            });
        },

        updateModelDefs() {
            // readOnly
            this.changeFieldsReadOnlyStatus(['attribute', 'channels', 'product', 'scope'], !this.model.get('isCustom') || this.model.get('inheritedFromParent'));

            if (this.model.get('attributeId')) {
                // prepare data
                let type = this.model.get('attributeType');
                let typeValue = this.model.get('typeValue');

                if (type) {
                    // prepare field defs
                    let fieldDefs = {
                        type: type,
                        options: typeValue,
                        view: type !== 'bool' ? this.getFieldManager().getViewName(type) : 'pim:views/fields/bool-required',
                        required: !!this.model.get('isRequired'),
                        readOnly: this.model.get('inheritedFromParent')
                    };

                    // for unit
                    if (type === 'unit') {
                        fieldDefs.measure = (typeValue || ['Length'])[0];
                    }

                    // enum and multiEnum is readOnly for locales attributes
                    if ((type === 'enum' || type === 'multiEnum') && this.model.get('locale')) {
                        fieldDefs.readOnly = true;
                    }

                    // set field defs
                    this.model.defs.fields.value = fieldDefs;
                }
            }

            this.runPipeline('updateModelDefs');
        },

        changeFieldsReadOnlyStatus(fields, condition) {
            fields.forEach(field => this.model.defs.fields[field].readOnly = condition);

            this.runPipeline('changeFieldsReadOnlyStatus', fields);
        },

        fetch() {
            let data = Dep.prototype.fetch.call(this);
            let view = this.getFieldView('value');
            if (view) {
                this.extendFieldData(view, data);
            }
            return data;
        },

        extendFieldData(view, data) {
            let additionalData = false;

            if (view.type === 'unit') {
                let actualFieldDefs = this.getMetadata().get(['fields', view.type, 'actualFields']) || [];
                let actualFieldValues = this.getFieldManager().getActualAttributes(view.type, view.name) || [];
                actualFieldDefs.forEach((field, i) => {
                    if (field) {
                        additionalData = additionalData || {};
                        additionalData[field] = data[actualFieldValues[i]];
                    }
                });
            }

            if (view.type === 'image') {
                _.extend((data || {}), {value: (data || {}).valueId});
            }

            if (additionalData) {
                _.extend((data || {}), {data: additionalData});
            }
        }

    })
);

