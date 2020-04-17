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

Espo.define('pim:views/product/fields/type', 'views/fields/enum',
    Dep => Dep.extend({

        data() {
            return _.extend({
                optionList: this.model.options || []
            }, Dep.prototype.data.call(this));
        },

        setupOptions() {
            var productType = Espo.Utils.clone(this.getMetadata().get('pim.productType'));
            var typeName = {};
            this.params.options = [];
            for (var type in productType) {
                this.params.options.push(type)
                typeName[type] = productType[type].name;
            }

            this.translatedOptions = Espo.Utils.clone(this.getLanguage().translate('type', 'options', 'Product') || {});
            // Add default name if not exist translate
            if(typeof this.translatedOptions !== 'object') {
                this.translatedOptions = typeName;
            }
        }

    })
);
