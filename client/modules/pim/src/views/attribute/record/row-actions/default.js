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

Espo.define('pim:views/attribute/record/row-actions/default', 'views/record/row-actions/default', function (Dep) {

    return Dep.extend({

        getActionList: function () {
            // get locale
            let locale = this.model.get('locale');

            let list = [];
            $.each(Dep.prototype.getActionList.call(this), function () {
                if (locale === null || (locale !== null && this.action !== 'quickRemove')) {
                    list.push(this);
                }
            });

            return list;
        }

    });

});
