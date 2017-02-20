define(function(require) {
    'use strict';

    var TreeManageComponent;
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var widgetManager = require('oroui/js/widget-manager');
    var messenger = require('oroui/js/messenger');
    var routing = require('routing');
    var BasicTreeManageComponent = require('oroui/js/app/components/basic-tree-manage-component');

    /**
     * @export oronavigation/js/app/components/tree-manage-component
     * @extends oroui.app.components.BasicTreeManageComponent
     * @class oronavigation.app.components.TreeManageComponent
     */
    TreeManageComponent = BasicTreeManageComponent.extend({
        /**
         * @property {String}
         */
        menu: '',

        /**
         * @property {Object}
         */
        context: {},

        /**
         * @property {Boolean}
         */
        checkboxEnabled: true,

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            TreeManageComponent.__super__.initialize.call(this, options);

            this.menu = options.menu;
            this.context = options.context;
        },

        /**
         * Triggers after node selection in tree
         *
         * @param {Event} e
         * @param {Object} selected
         */
        onSelect: function(e, selected) {
            if (this.initialization || !this.updateAllowed) {
                return;
            }
            var route;
            var routeParams;
            if (this.onRootSelectRoute && selected.node.parent === '#') {
                route = this.onRootSelectRoute;
                routeParams = {menuName: this.menu, context: this.context};
            } else {
                route = this.onSelectRoute;
                routeParams = {
                    context: this.context,
                    menuName: this.menu,
                    key: selected.node.id
                };
            }
            mediator.execute('redirectTo', {url: routing.generate(route, routeParams)});
        },

        /**
         * Triggers after node move
         *
         * @param {Object} e
         * @param {Object} data
         */
        onMove: function(e, data) {
            if (this.moveTriggered) {
                return;
            }

            var self = this;
            $.ajax({
                async: false,
                type: 'PUT',
                url: routing.generate(self.onMoveRoute, {
                    context: this.context,
                    menuName: this.menu
                }),
                data: {
                    key: data.node.id,
                    parentKey: data.parent,
                    position: data.position
                },
                success: function() {
                    if (self.reloadWidget) {
                        widgetManager.getWidgetInstanceByAlias(self.reloadWidget, function(widget) {
                            widget.render();
                        });
                    }
                    var message = __('oro.navigation.menuupdate.moved_success_message', {nodeText: data.node.text});
                    messenger.notificationFlashMessage('success', message);
                },
                errorHandlerMessage: false,
                error: function(xhr) {
                    self.rollback(data);
                    var message = __('oro.ui.jstree.move_node_error', {nodeText: data.node.text});
                    if (xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    messenger.notificationFlashMessage('error', message);
                }
            });
        }
    });

    return TreeManageComponent;
});
