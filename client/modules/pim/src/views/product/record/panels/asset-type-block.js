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

Espo.define('pim:views/product/record/panels/asset-type-block', 'dam:views/asset_relation/record/panels/asset-type-block',
    Dep => Dep.extend({
        setup() {
            this.listenTo(this.model, 'advanced-filters', () => {
                this.applyOverviewFilters();
            });
            Dep.prototype.setup.call(this);
            if (this.getMetadata().get(['scopes', this.model.get('entityName'), 'advancedFilters'])) {
                this.listenTo(this.model.get('entityModel'), 'overview-filters-changed', () => {
                    this.applyOverviewFilters();
                });
            }
        },
        applyOverviewFilters() {
            let rows = this.getListRows();
            let itemsWithChannelScope = [];
            Object.keys(rows).forEach(name => {
                let row = rows[name];
                this.controlRowVisibility(row, this.updateCheckByChannelFilter(row, itemsWithChannelScope));
            });
            this.hideChannelCategoriesWithGlobalScope(rows, itemsWithChannelScope);
        },

        updateCheckByChannelFilter(row, itemsWithChannelScope) {
            let hide = false;
            let currentChannelFilter = (this.model.get('entityModel').advancedEntityView || {}).channelsFilter;
            if (currentChannelFilter) {
                if (currentChannelFilter === 'onlyGlobalScope') {
                    hide = row.model.get('scope') !== 'Global';
                } else {
                    hide = (row.model.get('scope') === 'Channel' && !(row.model.get('channelsIds') || []).includes(currentChannelFilter));
                    if ((row.model.get('channelsIds') || []).includes(currentChannelFilter)) {
                        itemsWithChannelScope.push(row.model.get('id'));
                    }
                }
            }
            return hide;
        },

        getListRows() {
            let fields = {};
            let list = this.getView('list');
            if (list) {
                for (let row in list.nestedViews || {}) {
                    let rowView = list.getView(row);
                    if (rowView) {
                        fields[row] = rowView;
                    }
                }
            }
            return fields;
        },

        controlRowVisibility(row, hide) {
            if (hide) {
                row.$el.addClass('hidden');
            } else {
                row.$el.removeClass('hidden');
            }
        },

        hideChannelCategoriesWithGlobalScope(rows, itemsWithChannelScope) {
            Object.keys(rows).forEach(name => {
                let row = rows[name];
                if (itemsWithChannelScope.includes(row.model.get('id')) && row.model.get('scope') === 'Global') {
                    this.controlRowVisibility(row, true);
                }
            });
        },
    })
);