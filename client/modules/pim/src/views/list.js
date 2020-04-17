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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "KennerPIM"
 * word.
 */

Espo.define('pim:views/list', 'views/list',
    Dep => Dep.extend({

        setup() {
            this.quickCreate = this.getMetadata().get(`clientDefs.${this.scope}.quickCreate`);

            Dep.prototype.setup.call(this);
        },

        navigateToEdit(id) {
            let router = this.getRouter();
            let url = `#${this.scope}/view/${id}`;
            let canEdit =  this.getAcl().check(this.scope, 'edit');

            router.dispatch(this.scope, 'view', {
                id: id,
                mode: 'edit',
                setEditMode: canEdit
            });
            router.navigate(url, {trigger: false});
        },

        actionQuickCreate() {
            let options = _.extend({
                scope: this.scope,
                attributes: this.getCreateAttributes() || {}
            }, this.getMetadata().get(`clientDefs.${this.scope}.quickCreateOptions`) || {})

            this.notify('Loading...');
            let viewName = this.getMetadata().get('clientDefs.' + this.scope + '.modalViews.edit') || 'views/modals/edit';

            this.createView('quickCreate', viewName, options, function (view) {
                view.render();
                view.notify(false);
                this.listenToOnce(view, 'after:save', function () {
                    this.collection.fetch();

                    if (this.getMetadata().get(`clientDefs.${this.scope}.navigateToEntityAfterQuickCreate`)) {
                        this.navigateToEdit(view.getView('edit').model.id);
                    }
                }, this);
            }.bind(this));
        }
    })
);

