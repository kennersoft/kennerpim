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

Espo.define('pim:views/packaging/record/panels/products', 'views/record/panels/relationship',
    Dep => Dep.extend({

        boolFilterData: {
            notLinkedWithPackaging() {
                return this.model.id;
            }
        },

        setup() {
            Dep.prototype.setup.call(this);

            let select = this.actionList.find(item => item.action === (this.defs.selectAction || 'selectRelated'));

            if (select) {
                select.data.boolFilterListCallback = 'getSelectBoolFilterList';
                select.data.boolFilterDataCallback = 'getSelectBoolFilterData';
            }
        },

        getSelectBoolFilterList() {
            return this.defs.selectBoolFilterList || null
        },

        getSelectBoolFilterData(boolFilterList) {
            let data = {};
            if (Array.isArray(boolFilterList)) {
                boolFilterList.forEach(item => {
                    if (this.boolFilterData && typeof this.boolFilterData[item] === 'function') {
                        data[item] = this.boolFilterData[item].call(this);
                    }
                });
            }
            return data;
        }
    })
);
