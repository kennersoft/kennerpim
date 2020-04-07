/*
 * Pim
 * Free Extension
 * Copyright (c) 2020 Kenner Soft Service GmbH
 * Website: https://kennersoft.de
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "KennerPIM"
 * word.
 */

Espo.define('pim:views/product-attribute-value/modals/detail', 'views/modals/detail',
    Dep => Dep.extend({

        fullFormDisabled: true,

        actionEdit() {
            const viewName = this.getMetadata().get(['clientDefs', this.scope, 'modalViews', 'edit']) || 'views/modals/edit';
            const options = {
                scope: this.scope,
                id: this.id,
                fullFormDisabled: this.fullFormDisabled
            };

            this.handleRecordViewOptions(options);

            this.createView('quickEdit', viewName, options, function (view) {
                view.once('after:render', function () {
                    Espo.Ui.notify(false);
                    this.dialog.hide();
                }, this);

                this.listenToOnce(view, 'remove', function () {
                    this.dialog.show();
                }, this);

                this.listenToOnce(view, 'leave', function () {
                    this.remove();
                }, this);

                this.listenToOnce(view, 'after:save', function (model) {
                    this.trigger('after:save', model);

                    this.model.set(model.getClonedAttributes());
                }, this);

                view.render();
            }, this);
        },

        handleRecordViewOptions(options) {
            _.extend(options, {
                model: this.model
            });
        }
    })
);