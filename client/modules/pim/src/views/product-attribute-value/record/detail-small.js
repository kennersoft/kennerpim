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

Espo.define('pim:views/product-attribute-value/record/detail-small', ['pim:views/product-attribute-value/record/detail', 'views/record/detail-small'],
    (Detail, Dep) => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            Detail.prototype.handleValueModelDefsUpdating.call(this);
        },

        updateModelDefs() {
            Detail.prototype.updateModelDefs.call(this);
        },

        changeFieldsReadOnlyStatus(fields, condition) {
            Detail.prototype.changeFieldsReadOnlyStatus.call(this, fields, condition);
        },

        fetch() {
            return Dep.prototype.fetch.call(this);
        },

        extendFieldData(view, data) {
            Detail.prototype.extendFieldData.call(this, view, data);
        }

    })
);

