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

Espo.define('pim:views/product/record/panels/asset-relation-bottom-panel', 'dam:views/asset_relation/record/panels/bottom-panel',
    Dep => Dep.extend({
        additionalData: {},

        _createTypeBlock(model, show, callback) {
            let data = {
                entityName: this.defs.entityName,
                entityId  : this.model.id,
                entityModel  : this.model
            };
            model.set({...data, ...this.additionalData});
            this.createView(model.get('name'), "pim:views/product/record/panels/asset-type-block", {
                model: model,
                el   : this.options.el + ' .group[data-name="' + model.get("name") + '"]',
                sort : this.sort,
                show : show
            });
        }
    })
);