/**
 * @User: Rodine Mark Paul L. Villar <dean.villar@gmail.com>
 * @Date: 12/22/2015
 * @Time: 12:17 AM
 * @License: Copyright: Peter R. Spary - DBA Contacted.com
 */

var CallScripts = {
        contentContainer: "",
        ckModulePath: '',
        currentPage: 1,
        pageCache: {},
        mode: '',
        editorRel: 'callscripts_ck',
        ckeditor: false,
        mergeFields: {},
        updateDeleteButton: function () {
            if (this.currentPage == 1) {
                jq("#delete_callscript_page_button").hide();
            } else {
                jq("#delete_callscript_page_button").show();
            }
        },
        updateFormFromCache: function () {
            jq('input[name="pages"]').val(JSON.stringify(CallScripts.pageCache));
        },
        dialerInit: function () {
            this.currentPage = 1;
            this.ckeditor = false;
            jq(this.contentContainer).on('click', '.page_link_button', function (e) {
                var pageLink = e.srcElement || e.originalTarget;
                var page = jq(pageLink).attr('rel');
                Paginator.paginate(page, 1);
            });
            jq(this.contentContainer).on('click', '.collapsible_button', function (e) {
                var collapsebutton = e.srcElement || e.originalTarget;
                var div = jq(collapsebutton).next();
                if (jq(div).is(':visible')) {
                    jq(div).slideUp();
                } else {
                    jq(div).slideDown();
                }
            });
        },
        ckInit: function () {
            this.currentPage = 1;
            this.ckeditor = false;
            (function () {
                CKEDITOR.plugins.addExternal('callscript', CallScripts.ckModulePath, 'plugin.js');
            })();

            CKEDITOR.replace('ckeditor_content', {
                extraPlugins: 'callscript',
                contentsCss: CallScripts.ckModulePath + 'plugin.css',
                readOnly: CallScripts.mode == 'view',
                height: 500
            });

            CKEDITOR.on('instanceReady', function (evt) {
                var name = evt.editor.name;
                if (jq("#" + name).attr('rel') == CallScripts.editorRel) {
                    CallScripts.ckeditor = evt.editor;
                    evt.editor.on('change', function () {
                        if (CallScripts.ckeditor.checkDirty()) {
                            CallScripts.pageCache[CallScripts.currentPage] = CallScripts.ckeditor.getData();
                            CallScripts.updateFormFromCache();
                            CallScripts.attachCallScriptCkEvents();
                        }
                    });
                    CallScripts.setContent(CallScripts.pageCache[1]);
                    CallScripts.attachCallScriptCkEvents();
                }
            });

            CKEDITOR.on('dialogDefinition', function (evt) {
                var name = evt.editor.name;
                if (jq("#" + name).attr('rel') == CallScripts.editorRel) {
                    var dialogName = evt.data.name;
                    var dialogDefinition = evt.data.definition;
                    if (dialogName == 'page_link_dialog') {
                        CallScripts.setupPageLinkDialog(dialogDefinition);
                    } else if (dialogName == 'collapse_dialog') {
                        CallScripts.setupCollapsibleTextDialog(dialogDefinition);
                    }
                }
            });
            this.updateDeleteButton();
        },
        paginate: function (page) {
            if (this.pageCache.hasOwnProperty(page)) {
                this.setContent(CallScripts.pageCache[page]);
                this.currentPage = page;
                this.updateDeleteButton();
                this.attachCallScriptCkEvents();
                if (this.mode == 'dialer' && typeof Dialer.autoscrollstart == 'function') {
                    jq(this.contentContainer).scrollTop(0);
                    if (Dialer.autoscrollinstance) {
                        Dialer.autoscrollstop(true);
                        Dialer.autoscrollstart(true);
                    }
                }
            }
        },
        setContent: function (content) {
            if (this.ckeditor) {
                this.ckeditor.setData(content);
            } else {
                jq(this.contentContainer).html(content);
            }
        },
        addPage: function () {
            var pages = Object.keys(this.pageCache);
            var new_page = 2;
            if (pages.length) {
                var max = Math.max.apply(null, pages);
                new_page = max + 1;
            }
            if (new_page >= 100) {
                alert("You can only add up to 99 pages for a template.");
                return;
            }
            this.pageCache[new_page] = "";
            CallScripts.updateFormFromCache();
            //TODO: Temporary
            items_total = new_page;
            Paginator.paginate(new_page, 1);
        },
        deletePage: function () {
            if (this.currentPage > 1) {
                var context = this;
                Contacted.confirm("Are you sure you want to delete page " + this.currentPage + "?").then(function (result) {
                    if (result) {
                        delete context.pageCache[context.currentPage];
                        for (var page in context.pageCache) {
                            var pageInt = parseInt(page);
                            if (pageInt > parseInt(context.currentPage)) {
                                var content = context.pageCache[page];
                                context.pageCache[pageInt - 1] = content;
                                delete context.pageCache[page];
                            }
                        }
                        CallScripts.updateFormFromCache();
                        //TODO: Temporary
                        items_total = items_total - 1;
                        Paginator.paginate(context.currentPage >= items_total ? items_total : context.currentPage, 1);
                    }
                });
            }
        },
        ckExec: function (cmd) {
            if (this.ckeditor) {
                this.ckeditor.execCommand(cmd);
            }
        },
        setupPageLinkDialog: function (dialogDefinition) {
            var gen_tab = dialogDefinition.getContents("general");
            var page_number_def = gen_tab.get('page_number');
            page_number_def['setup'] = function (element) {
                var element_id = '#' + this.getInputElement().$.id;
                var number_of_pages = Math.ceil(items_total / items_per_page);
                if (number_of_pages > 0) jq(element_id).html('');
                for (var i = 1; i <= number_of_pages; i++) {
                    var page = new Option('Page ' + i, i);
                    jq(page).html('Page ' + i);
                    jq(element_id).append(page);
                }
                if (element.hasAttribute('rel')) {
                    jq(element_id).val(element.getAttribute('rel'));
                }
            };
            page_number_def['default'] = 1;
        },
        setupCollapsibleTextDialog: function (dialogDefinition) {
            var gen_tab = dialogDefinition.getContents("general");
            var merge_field_def = gen_tab.get('merge_field');

            var mfs = Array();
            jq.each(this.mergeFields, function (ident, mergeArr) {
                jq.each(mergeArr, function (key, value) {
                    var mf = new Array(ident + " " + value, key);
                    mfs.push(mf);
                });
            });
            merge_field_def['items'] = mfs;
            merge_field_def['default'] = 'last_name';
        },
        attachCallScriptCkEvents: function () {
            if (this.ckeditor.document) {
                var elements = this.ckeditor.document.getElementsByTag('input');
                for (var i = 0, c = elements.count(); i < c; i++) {
                    var e = new CKEDITOR.dom.element(elements.$.item(i));
                    if (e.getAttribute('class') == 'page_link_button' && !e.hasListeners('click')) {
                        e.on('click', function (ev) {
                            var input = ev.data.getTarget();
                            var page = input.getAttribute('rel');
                            Paginator.paginate(page, 1);
                        });
                    }
                }

                elements = this.ckeditor.document.getElementsByTag('a');
                for (var i = 0, c = elements.count(); i < c; i++) {
                    var e = new CKEDITOR.dom.element(elements.$.item(i));
                    if (e.getAttribute('class') == 'collapsible_button' && !e.hasListeners('click')) {
                        e.on('click', function (ev) {
                            var input = ev.data.getTarget();
                            var div = input.getNext().$;
                            if (e.getNext().isVisible()) {
                                jq(div).slideUp('fast');
                            } else {
                                jq(div).slideDown('fast');
                            }
                        });
                    }
                }
            }
        }

    }
    ;
