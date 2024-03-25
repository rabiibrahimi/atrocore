/*
 * This file is part of EspoCRM and/or AtroCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * AtroCore is EspoCRM-based Open Source application.
 * Copyright (C) 2020 AtroCore GmbH.
 *
 * AtroCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AtroCore as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "AtroCore" word.
 */

Espo.define('views/compare', ['views/main'], function (Dep) {

    return Dep.extend({

        template: 'compare',

        el: '#main',

        scope: null,

        name: 'Compare',
        headerView: 'views/header',
        recordView: 'views/record/compare',
        setup: function () {

            Dep.prototype.setup.call(this);
            this.model = this.options.model;
            this.scope = this.model.urlRoot;
            this.recordView = this.getMetadata().get('clientDefs.'+this.scope+'.compare.record') ?? 'views/record/compare'
            this.updatePageTitle();
            this.setupHeader();
            this.setupRecord()

        },

        setupHeader: function () {
            this.createView('header', this.headerView, {
                model: this.model,
                el: '#main > .page-header'
            });
        },
        getHeader: function () {

            var headerIconHtml = this.getHeaderIconHtml();

            var arr = [];

            if (this.options.noHeaderLinks) {
                arr.push(this.getLanguage().translate(this.scope, 'scopeNamesPlural'));
            } else {
                var rootUrl = this.options.rootUrl || this.options.params.rootUrl || '#' + this.scope;
                arr.push(headerIconHtml + '<a href="' + rootUrl + '" class="action" data-action="navigateToRoot">' + this.getLanguage().translate(this.scope, 'scopeNamesPlural') + '</a>');
            }

            var name = Handlebars.Utils.escapeExpression(this.model.get('name'));

            if (name === '') {
                name = this.model.id;
            }

            if (this.options.noHeaderLinks) {
                arr.push(name);
            } else {
                arr.push('<a href="#' + this.scope + '/view/' + this.model.id + '" class="action">' + name + '</a>');
            }
            arr.push(this.getLanguage().translate('Compare'));
            arr.push(this.model.get('name'));

            return this.buildHeaderHtml(arr);
        },

        setupRecord(name = 'record') {
            this.notify('Loading...');
            this.ajaxGetRequest(`Connector/action/distantEntity?entityType=${this.scope}&id=${this.model.id}`, null, {async: false}).success(attr => {
                this.notify(false);
                var o = {
                    model: this.model,
                    distantModelAttribute: attr,
                    el: '#main > .'+name,
                    scope: this.scope
                };
                this.createView(name, this.recordView, o);
            })

        },

        getMenu(){
          return {
              "buttons": [
              ]
          }
        },

        updatePageTitle: function () {
            this.setPageTitle(this.getLanguage().translate('Compare')+' '+this.scope+' '+this.model.get('name'));
        },

    });
});

