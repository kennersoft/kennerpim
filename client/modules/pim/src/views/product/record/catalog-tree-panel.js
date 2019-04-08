/*
 * Pim
 * Free Extension
 * Copyright (c) TreoLabs GmbH
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

Espo.define('pim:views/product/record/catalog-tree-panel', 'view',
    Dep => Dep.extend({

        template: 'pim:product/record/catalog-tree-panel',

        catalogs: [],

        categories: [],

        events: {
            'click .category-buttons button[data-action="selectAll"]': function (e) {
                this.selectCategoryButtonApplyFilter($(e.currentTarget), {type: 'anyOf', category: {}});
            },
            'click .category-buttons button[data-action="selectWithoutCategory"]': function (e) {
                this.selectCategoryButtonApplyFilter($(e.currentTarget), {type: 'isEmpty'});
            },
            'click button[data-action="collapsePanel"]': function () {
                this.actionCollapsePanel();
            }
        },

        data() {
            return {
                scope: this.scope,
                catalogDataList: this.getCatalogDataList()
            }
        },

        setup() {
            this.scope = this.options.scope || this.scope;
            this.wait(true);

            Promise.all([this.ajaxGetRequest('Catalog'), this.ajaxGetRequest('Category')]).then(response => {
                this.catalogs = response[0].list || [];
                let categoryList = this.categories = response[1].list || [];
                categoryList = this.getCategoryTree(this.catalogs, categoryList);
                this.catalogs.forEach(catalog => {
                    catalog.categoryTree = categoryList.find(category => catalog.categoryId === category.id);
                });
                this.setupPanels();
                this.wait(false);
            });

            this.listenTo(this, 'resetFilters', () => {
                this.selectCategoryButtonApplyFilter(this.$el.find('button[data-action="selectAll"]'), false);
            });
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if ($(window).width() <= 767) {
                this.actionCollapsePanel();
            }
        },

        selectCategoryButtonApplyFilter(button, filterParams) {
            this.selectCategoryButton(button);
            if ($(window).width() <= 767) {
                this.actionCollapsePanel(true);
            }
            if (filterParams) {
                this.applyCategoryFilter(filterParams.type, filterParams.category);
            }
        },

        getCategoryTree(catalogList, categoryList) {
            let rootCategories = categoryList.filter(item => catalogList.find(catalog => catalog.categoryId === item.id));
            categoryList = categoryList.filter(item => !!item.categoryParentId);

            let getParentsWithChildren = (parents) => {
                if (parents.length) {
                    let children = [];
                    categoryList.forEach(item => {
                        if (parents.find(parent => parent.id === item.categoryParentId)) {
                            children.push(item);
                        }
                    });

                    if (children.length) {
                        children = getParentsWithChildren(children);
                    }

                    children.forEach(child => {
                        let parent = parents.find(parent => parent.id === child.categoryParentId);
                        if (parent) {
                            parent.children = parent.children || [];
                            if (!parent.children.find(item => item.id === child.id)) {
                                parent.children.push(child);
                            }
                        }
                    });
                }
                return parents;
            };

            return getParentsWithChildren(rootCategories);
        },

        setupPanels() {
            this.createView('categorySearch', 'pim:views/product/record/catalog-tree-panel/category-search', {
                el: '.catalog-tree-panel > .category-panel > .category-search',
                scope: this.scope,
                catalogs: this.catalogs,
                categories: this.categories
            }, view => {
                view.render();
                this.listenTo(view, 'category-search-select', category => {
                    this.selectCategory(category, true);
                });
            });

            this.catalogs.forEach(catalog => {
                if (catalog.categoryTree) {
                    this.createView('category-tree-' + catalog.id, 'pim:views/product/record/catalog-tree-panel/category-tree', {
                        name: catalog.id,
                        el: `.catalog-tree-panel > .category-panel > .category-tree > .panel[data-name="${catalog.id}"]`,
                        scope: this.scope,
                        catalog: catalog
                    }, view => {
                        view.render();
                        view.listenTo(view, 'category-tree-select', ((categoryId, catalogId) => {
                            let category = this.categories.find(category => category.id === categoryId) || {};
                            category.catalogId = catalogId;
                            this.selectCategory(category);
                        }));
                    });
                }
            });
        },

        selectCategory(category, notSkipCollapse) {
            if (category && category.id && category.catalogId) {
                this.setCategoryActive(category.id, category.catalogId);
                if ($(window).width() <= 767) {
                    this.actionCollapsePanel();
                }
                if (notSkipCollapse) {
                    this.collapseCategory(category.id, category.catalogId);
                }
                this.applyCategoryFilter('anyOf', category);
            }
        },

        applyCategoryFilter(type, category) {
            let data = {};
            if (type === 'isEmpty') {
                data = {
                    type: 'isNotLinked',
                    data: {
                        type: type
                    }
                };
            } else if (type === 'anyOf') {
                data = {
                    type: 'linkedWith',
                    value: category.id ? [category.id] : [],
                    nameHash: category.id ? {[category.id]: category.name} : {},
                    data: {
                        type: type
                    }
                };
            }
            this.trigger('select-category', data);
        },

        collapseCategory(id, catalogId) {
            let activeCategory = this.$el.find(`.panel[data-name="${catalogId}"] li.child[data-id="${id}"]:eq()`);
            activeCategory.parents('.panel-collapse.collapse').collapse('show');
        },

        setCategoryActive(id, catalogId) {
            this.$el.find('.category-buttons > button').removeClass('active');
            this.$el.find('ul.list-group-tree li.child').removeClass('active');
            if (catalogId) {
                this.$el.find(`.panel[data-name="${catalogId}"] li.child[data-id="${id}"]:eq()`).addClass('active');
            } else {
                this.$el.find(`li.child[data-id="${id}"]:eq()`).addClass('active');
            }
        },

        selectCategoryButton(button) {
            this.$el.find('.panel-collapse.collapse[class^="catalog-"].in').collapse('hide');
            this.$el.find('ul.list-group-tree li.child').removeClass('active');
            this.$el.find('.category-buttons > button').removeClass('active');
            button.addClass('active');
        },

        actionCollapsePanel(forceHide) {
            let categoryPanel = this.$el.find('.category-panel');
            let button = this.$el.find('button[data-action="collapsePanel"]');
            let listContainer = this.$el.parent('#main').find('.list-container');
            if (categoryPanel.hasClass('hidden') && !forceHide) {
                categoryPanel.removeClass('hidden');
                button.removeClass('collapsed');
                button.find('span.toggle-icon-left').removeClass('hidden');
                button.find('span.toggle-icon-right').addClass('hidden');
                this.$el.removeClass('catalog-tree-panel-hidden');
                this.$el.addClass('col-xs-12 col-lg-3');
                listContainer.removeClass('hidden-catalog-tree-panel');
                listContainer.addClass('col-xs-12 col-lg-9');
            } else {
                categoryPanel.addClass('hidden');
                button.addClass('collapsed');
                button.find('span.toggle-icon-left').addClass('hidden');
                button.find('span.toggle-icon-right').removeClass('hidden');
                this.$el.removeClass('col-xs-12 col-lg-3');
                this.$el.addClass('catalog-tree-panel-hidden');
                listContainer.removeClass('col-xs-12 col-lg-9');
                listContainer.addClass('hidden-catalog-tree-panel');
            }
            $(window).trigger('resize');
        },

        getCatalogDataList: function () {
            let arr = [];
            this.catalogs.forEach(catalog => {
                if (catalog.categoryTree) {
                    arr.push({
                        key: 'category-tree-' + catalog.id,
                        name: catalog.id
                    });
                }
            });
            return arr;
        },
    })
);