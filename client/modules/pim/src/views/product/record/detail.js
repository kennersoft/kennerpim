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

Espo.define('pim:views/product/record/detail', 'pim:views/record/detail',
    Dep => Dep.extend({

        template: 'pim:product/record/detail',

        catalogTreeData: null,

        notSavedFields: ['image'],

        isCatalogTreePanel: false,

        isCreatingVariant: false,

        setup() {
            if (this.isVariantAllowed()) {
                this.dropdownItemList.push({
                    'label': this.translate('createVariantAction', 'messages', 'Product'),
                    'name': 'createProductVariant'
                });
            }

            Dep.prototype.setup.call(this);

            if (!this.model.isNew() && (this.type === 'detail' || this.type === 'edit') && this.getMetadata().get(['scopes', this.scope, 'advancedFilters'])) {
                this.listenTo(this.model, 'main-image-updated', () => {
                    this.applyOverviewFilters();
                });
            }

            if (!this.isWide && this.type !== 'editSmall' && this.type !== 'detailSmall'
                && this.getAcl().check('Catalog', 'read') && this.getAcl().check('Category', 'read')
                && this.getMetadata().get(['scopes', 'Product', 'catalogTreePanelActive'])) {
                this.isCatalogTreePanel = true;
                this.setupCatalogTreePanel();
            }
        },

        isVariantAllowed() {
            return !this.model.get('parentProductId') && this.getConfig().get('variantsEnabled');
        },

        setupCatalogTreePanel() {
            this.createView('catalogTreePanel', 'pim:views/product/record/catalog-tree-panel', {
                el: `${this.options.el} .catalog-tree-panel`,
                scope: this.scope,
                model: this.model
            }, view => {
                view.listenTo(view, 'select-category', data => this.navigateToList(data));
            });
        },

        navigateToList(data) {
            this.catalogTreeData = Espo.Utils.cloneDeep(data || {});
            const options = {
                isReturn: true,
                callback: this.expandCatalogTree.bind(this)
            };
            this.getRouter().navigate(`#${this.scope}`);
            this.getRouter().dispatch(this.scope, null, options);
        },

        expandCatalogTree(list) {
            list.sortCollectionWithCatalogTree(this.catalogTreeData);
            list.render();
        },

        data() {
            return _.extend({
                isCatalogTreePanel: this.isCatalogTreePanel
            }, Dep.prototype.data.call(this))
        },

        applyOverviewFilters() {
            // fields filter
            this.fieldsFilter();

            // multi-language fields filter
            this.multiLangFieldsFilter();

            // hide generic fields
            this.genericFieldsFilter();

            // trigger
            this.model.trigger('overview-filters-applied');
        },

        getFilterFieldViews: function () {
            let fields = {};
            $.each(this.getFieldViews(), function (name, fieldView) {
                if (!fieldView.model.getFieldParam(name, 'advancedFilterDisabled')) {
                    fields[name] = fieldView;
                }
            });

            return fields;
        },

        fieldsFilter: function () {
            // get filter param
            let filter = (this.model.advancedEntityView || {}).fieldsFilter;

            $.each(this.getFilterFieldViews(), (name, fieldView) => {
                let actualFields = this.getFieldManager().getActualAttributeList(fieldView.model.getFieldType(name), name);
                let actualFieldValues = actualFields.map(field => fieldView.model.get(field));
                actualFieldValues = actualFieldValues.concat(this.getAlternativeValues(fieldView));

                let hide = !actualFieldValues.every(value => this.checkFieldValue(filter, value, fieldView.isRequired()));
                this.controlFieldVisibility(fieldView, hide);
            });
        },

        multiLangFieldsFilter: function () {
            // get locale
            let locale = (this.model.advancedEntityView || {}).localesFilter;

            $.each(this.getFilterFieldViews(), function (name, fieldView) {
                let multilangLocale = fieldView.model.getFieldParam(name, 'multilangLocale');

                if (multilangLocale !== null) {
                    if (locale !== null && locale !== '' && multilangLocale !== locale) {
                        fieldView.hide();
                    } else if (!fieldView.$el.hasClass('hidden')) {
                        fieldView.show();
                    }
                }
            });
        },

        genericFieldsFilter: function () {
            // prepare is show param
            let isShow = (this.model.advancedEntityView || {}).showGenericFields;

            $.each(this.getFilterFieldViews(), (name, fieldView) => {
                let field = fieldView.model.getFieldParam(name, 'multilangField');
                const view = this.getFieldView(field);

                if (field !== null && view) {
                    if (isShow && !view.$el.hasClass('hidden')) {
                        view.show();
                    } else {
                        view.hide();
                    }
                }
            });
        },

        hotKeySave: function (e) {
            e.preventDefault();
            if (this.mode === 'edit') {
                this.actionSave();
            } else {
                let viewsFields = this.getFieldViews();
                Object.keys(viewsFields).forEach(item => {
                    if (viewsFields[item].mode === "edit" ) {
                        viewsFields[item].inlineEditSave();
                    }
                });
            }
        },

        afterNotModified(notShow) {
            if (!notShow) {
                let msg = this.translate('notModified', 'messages');
                Espo.Ui.warning(msg, 'warning');
            }
            this.enableButtons();
        },

        getBottomPanels() {
            let bottomView = this.getView('bottom');
            if (bottomView) {
                return bottomView.nestedViews;
            }
            return null;
        },

        setDetailMode() {
            let panels = this.getBottomPanels();
            if (panels) {
                for (let panel in panels) {
                    const view = panels[panel];
                    if (typeof view.setListMode === 'function' && (!view.mode || view.mode === 'edit')) {
                        view.setListMode();
                    }
                }
            }
            Dep.prototype.setDetailMode.call(this);
        },

        setEditMode() {
            let panels = this.getBottomPanels();
            if (panels) {
                for (let panel in panels) {
                    const view = panels[panel];
                    if (typeof view.setEditMode === 'function' && view.mode !== 'edit') {
                        view.setEditMode();
                    }
                }
            }
            Dep.prototype.setEditMode.call(this);
        },

        cancelEdit() {
            let panels = this.getBottomPanels();
            if (panels) {
                for (let panel in panels) {
                    const view = panels[panel];
                    if (typeof view.cancelEdit === 'function' && view.mode === 'edit') {
                        view.cancelEdit();
                    }
                }
            }
            Dep.prototype.cancelEdit.call(this);
        },

        handlePanelsFetch() {
            let changes = false;
            let panels = this.getBottomPanels();
            if (panels) {
                for (let panel in panels) {
                    const view = panels[panel];
                    if (typeof view.panelFetch === 'function' && view.mode === 'edit') {
                        changes = view.panelFetch() || changes;
                    }
                }
            }
            return changes;
        },

        validatePanels() {
            let notValid = false;
            let panels = this.getBottomPanels();
            if (panels) {
                for (let panel in panels) {
                    const view = panels[panel];
                    if (typeof view.validate === 'function' && view.mode === 'edit') {
                        notValid = view.validate() || notValid;
                    }
                }
            }
            return notValid
        },

        handlePanelsSave() {
            let panels = this.getBottomPanels();
            if (panels) {
                for (let panel in panels) {
                    const view = panels[panel];
                    if (typeof view.save === 'function' && view.mode === 'edit') {
                        view.save();
                    }
                }
            }
        },

        save(callback, skipExit) {
            (this.notSavedFields || []).forEach(field => {
                const keys = this.getFieldManager().getAttributeList(this.model.getFieldType(field), field);
                keys.forEach(key => delete this.model.attributes[key]);
            });

            this.beforeBeforeSave();

            let data = this.fetch();

            let self = this;
            let model = this.model;

            let initialAttributes = this.attributes;

            let beforeSaveAttributes = this.model.getClonedAttributes();

            data = _.extend(Espo.Utils.cloneDeep(beforeSaveAttributes), data);

            let gridInitPackages = false;
            let packageView = false;
            let bottomView = this.getView('bottom');
            if (bottomView) {
                packageView = bottomView.getView('productTypePackages');
                if (packageView) {
                    gridInitPackages = packageView.getInitAttributes();
                }
            }

            let attrs = false;
            let gridPackages = false;
            if (model.isNew()) {
                attrs = data;
            } else {
                for (let name in data) {
                    if (name !== 'id'&& gridInitPackages && Object.keys(gridInitPackages).indexOf(name) > -1) {
                        if (!_.isEqual(gridInitPackages[name], data[name])) {
                            (gridPackages || (gridPackages = {}))[name] = data[name];
                        }
                        continue;
                    }

                    if (_.isEqual(initialAttributes[name], data[name])) {
                        continue;
                    }
                    (attrs || (attrs = {}))[name] = data[name];
                }
            }

            let beforeSaveGridPackages = false;
            if (gridPackages && packageView) {
                let gridModel = packageView.getView('grid').model;
                beforeSaveGridPackages = gridModel.getClonedAttributes();
                gridModel.set(gridPackages, {silent: true})
            }

            if (attrs) {
                model.set(attrs, {silent: true});
            }

            const panelsChanges = this.handlePanelsFetch();

            const overviewValidation = this.validate();
            const panelValidation = this.validatePanels();

            if (overviewValidation || panelValidation) {
                if (gridPackages && packageView && beforeSaveGridPackages) {
                    packageView.getView('grid').model.attributes = beforeSaveGridPackages;
                }

                model.attributes = beforeSaveAttributes;

                this.trigger('cancel:save');
                this.afterNotValid();
                return;
            }

            if (gridPackages && packageView) {
                packageView.save();
            }

            if (panelsChanges) {
                this.handlePanelsSave();
            }

            if (!attrs) {
                this.afterNotModified(gridPackages || panelsChanges);
                this.trigger('cancel:save');
                return true;
            }

            this.beforeSave();

            this.trigger('before:save');
            model.trigger('before:save');

            model.save(attrs, {
                success: function () {
                    this.afterSave();
                    if (self.isNew) {
                        self.isNew = false;
                    }
                    this.trigger('after:save');
                    model.trigger('after:save');

                    if (!callback) {
                        if (!skipExit) {
                            if (self.isNew) {
                                this.exit('create');
                            } else {
                                this.exit('save');
                            }
                        }
                    } else {
                        callback(this);
                    }
                }.bind(this),
                error: function (e, xhr) {
                    let r = xhr.getAllResponseHeaders();
                    let response = null;

                    if (xhr.status == 409) {
                        let header = xhr.getResponseHeader('X-Status-Reason');
                        try {
                            let response = JSON.parse(header);
                        } catch (e) {
                            console.error('Error while parsing response');
                        }
                    }

                    if (xhr.status == 400) {
                        if (!this.isNew) {
                            this.model.set(this.attributes);
                        }
                    }

                    if (response) {
                        if (response.reason == 'Duplicate') {
                            xhr.errorIsHandled = true;
                            self.showDuplicate(response.data);
                        }
                    }

                    this.afterSaveError();

                    model.attributes = beforeSaveAttributes;
                    self.trigger('cancel:save');

                }.bind(this),
                patch: !model.isNew()
            });
            return true;
        },

        actionCreateProductVariant() {
            if (this.isCreatingVariant) {
                return;
            }

            this.isCreatingVariant = true;

            this.notify('Processing...');
            this.ajaxPostRequest('Product/action/CreateVariantFromProduct', {parent_product_id: this.model.id})
                .then(response => {
                    this.notify('Success', 'success');
                    window.location.href = `/#Product/view/${response.id}`;
                }, e => {
                    this.notify('Error', 'error');
                    this.isCreatingVariant = false;
                });
        }
    })
);

