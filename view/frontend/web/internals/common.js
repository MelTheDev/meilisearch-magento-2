define(
    [
        'jquery',
        'algoliaBundle'
    ], function ($, algoliaBundle) {

        window.algolia = {
        deprecatedHooks: [
            'beforeAutocompleteProductSourceOptions',
            'beforeAutocompleteSources'
        ],
        allowedHooks: [
            'beforeAutocompleteSources', // Older implementations incompatible with v1 API
            'afterAutocompleteSources',
            'afterAutocompletePlugins',
            'beforeAutocompleteOptions',
            'afterAutocompleteOptions',
            'afterAutocompleteStart',
            'beforeAutocompleteProductSourceOptions',
            'afterAutocompleteProductSourceOptions',
            'beforeInstantsearchInit',
            'beforeWidgetInitialization',
            'beforeInstantsearchStart',
            'afterInstantsearchStart',
            'afterInsightsBindEvents'
        ],
        registeredHooks: [],
        registerHook: function (hookName, callback) {
            if (this.allowedHooks.indexOf(hookName) === -1) {
                throw 'Hook "' + hookName + '" cannot be defined. Please use one of ' + this.allowedHooks.join(', ');
            }

            if (this.deprecatedHooks.indexOf(hookName) > -1) {
                console.warn(`Algolia Autocomplete: ${hookName} has been deprecated and may not be supported in a future release.`);
            }

            if (!this.registeredHooks[hookName]) {
                this.registeredHooks[hookName] = [callback];
            } else {
                this.registeredHooks[hookName].push(callback);
            }
        },
        getRegisteredHooks: function (hookName) {
            if (this.allowedHooks.indexOf(hookName) === -1) {
                throw 'Hook "' + hookName + '" cannot be defined. Please use one of ' + this.allowedHooks.join(', ');
            }

            if (!this.registeredHooks[hookName]) {
                return [];
            }

            return this.registeredHooks[hookName];
        },
        triggerHooks: function () {
            var hookName = arguments[0],
                originalData = arguments[1],
                hookArguments = Array.prototype.slice.call(arguments, 2);

            // console.log("Invoking hook", hookName);

            var data = this.getRegisteredHooks(hookName).reduce(function (currentData, hook) {
                if (Array.isArray(currentData)) {
                    currentData = [currentData];
                }
                var allParameters = [].concat(currentData).concat(hookArguments);
                return hook.apply(null, allParameters);
            }, originalData);

            return data;
        }
    };

    window.isMobile = function () {
        var check = false;

        (function (a) {
            if (/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a) || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0, 4))) check = true;
        })(navigator.userAgent || navigator.vendor || window.opera);

        return check;
    };

    window.getCookie = function (name) {
        var value = "; " + document.cookie;
        var parts = value.split("; " + name + "=");
        if (parts.length == 2) {
            return parts.pop().split(";").shift();
        }

        return "";
    };

    window.transformHit = function (hit, price_key, helper) {
        if (Array.isArray(hit.categories))
            hit.categories = hit.categories.join(', ');

        if (hit._highlightResult.categories_without_path && Array.isArray(hit.categories_without_path)) {
            hit.categories_without_path = $.map(hit._highlightResult.categories_without_path, function (category) {
                return category.value;
            });

            hit.categories_without_path = hit.categories_without_path.join(', ');
        }

        var matchedColors = [];

        if (helper && algoliaConfig.useAdaptiveImage === true) {
            if (hit.images_data && helper.state.facetsRefinements.color) {
                matchedColors = helper.state.facetsRefinements.color.slice(0); // slice to clone
            }

            if (hit.images_data && helper.state.disjunctiveFacetsRefinements.color) {
                matchedColors = helper.state.disjunctiveFacetsRefinements.color.slice(0); // slice to clone
            }
        }

        if (Array.isArray(hit.color)) {
            var colors = [];

            $.each(hit._highlightResult.color, function (i, color) {
                if (color.matchLevel === undefined || color.matchLevel === 'none') {
                    return;
                }

                colors.push(color);

                if (algoliaConfig.useAdaptiveImage === true) {
                    var matchedColor = color.matchedWords.join(' ');
                    if (hit.images_data && color.fullyHighlighted && color.fullyHighlighted === true) {
                        matchedColors.push(matchedColor);
                    }
                }
            });

            hit._highlightResult.color = colors;
        } else {
            if (hit._highlightResult.color && hit._highlightResult.color.matchLevel === 'none') {
                hit._highlightResult.color = {value: ''};
            }
        }

        if (algoliaConfig.useAdaptiveImage === true) {
            $.each(matchedColors, function (i, color) {
                color = color.toLowerCase();

                if (hit.images_data[color]) {
                    hit.image_url = hit.images_data[color];
                    hit.thumbnail_url = hit.images_data[color];

                    return false;
                }
            });
        }

        if (hit._highlightResult.color && hit._highlightResult.color.value && hit.categories_without_path) {
            if (hit.categories_without_path.indexOf('<em>') === -1 && hit._highlightResult.color.value.indexOf('<em>') !== -1) {
                hit.categories_without_path = '';
            }
        }

        if (Array.isArray(hit._highlightResult.name))
            hit._highlightResult.name = hit._highlightResult.name[0];

        if (Array.isArray(hit.price)) {
            hit.price = hit.price[0];
            if (hit['price'] !== undefined && price_key !== '.' + algoliaConfig.currencyCode + '.default' && hit['price'][algoliaConfig.currencyCode][price_key.substr(1) + '_formated'] !== hit['price'][algoliaConfig.currencyCode]['default_formated']) {
                hit['price'][algoliaConfig.currencyCode][price_key.substr(1) + '_original_formated'] = hit['price'][algoliaConfig.currencyCode]['default_formated'];
            }

            if (hit['price'][algoliaConfig.currencyCode]['default_original_formated']
                && hit['price'][algoliaConfig.currencyCode]['special_to_date']) {
                var priceExpiration = hit['price'][algoliaConfig.currencyCode]['special_to_date'];

                if (algoliaConfig.now > priceExpiration + 1) {
                    hit['price'][algoliaConfig.currencyCode]['default_formated'] = hit['price'][algoliaConfig.currencyCode]['default_original_formated'];
                    hit['price'][algoliaConfig.currencyCode]['default_original_formated'] = false;
                }
            }
        }

        /* Added code to bind default bundle options for add to cart */
        if (hit.default_bundle_options) {
            var default_bundle_option = [];
            for (const property in hit.default_bundle_options) {
                const optionsData = {
                    optionId: property,
                    selectionId : hit.default_bundle_options[property]
                }
                default_bundle_option.push(optionsData);
            }
            hit._highlightResult.default_bundle_options = default_bundle_option;
        }

        // Add to cart parameters
        var action = algoliaConfig.instant.addToCartParams.action + 'product/' + hit.objectID + '/';

        var correctFKey = getCookie('form_key');

        if (correctFKey != "" && algoliaConfig.instant.addToCartParams.formKey != correctFKey) {
            algoliaConfig.instant.addToCartParams.formKey = correctFKey;
        }

        hit.addToCart = {
            'action': action,
            'uenc': AlgoliaBase64.mageEncode(action),
            'formKey': algoliaConfig.instant.addToCartParams.formKey
        };

        if (hit.__queryID) {

            hit.urlForInsights = hit.url;

            if (algoliaConfig.ccAnalytics.enabled
                && algoliaConfig.ccAnalytics.conversionAnalyticsMode !== 'disabled') {
                var insightsDataUrlString = $.param({
                    queryID: hit.__queryID,
                    objectID: hit.objectID,
                    indexName: hit.__indexName
                });
                if (hit.url.indexOf('?') > -1) {
                    hit.urlForInsights += insightsDataUrlString
                } else {
                    hit.urlForInsights += '?' + insightsDataUrlString;
                }
            }
        }

        return hit;
    };

    window.fixAutocompleteCssHeight = function () {
        if ($(document).width() > 768) {
            $(".other-sections").css('min-height', '0');
            $(".aa-dataset-products").css('min-height', '0');
            var height = Math.max($(".other-sections").outerHeight(), $(".aa-dataset-products").outerHeight());
            $(".aa-dataset-products").css('min-height', height);
        }
    };

    window.fixAutocompleteCssSticky = function (menu) {
        var dropdown_menu = $('#algolia-autocomplete-container .aa-dropdown-menu');
        var autocomplete_container = $('#algolia-autocomplete-container');
        autocomplete_container.removeClass('reverse');

        /** Reset computation **/
        dropdown_menu.css('top', '0px');

        /** Stick menu vertically to the input **/
        var targetOffset = Math.round(menu.offset().top + menu.outerHeight());
        var currentOffset = Math.round(autocomplete_container.offset().top);

        dropdown_menu.css('top', (targetOffset - currentOffset) + 'px');

        if (menu.offset().left + menu.outerWidth() / 2 > $(document).width() / 2) {
            /** Stick menu horizontally align on right to the input **/
            dropdown_menu.css('right', '0px');
            dropdown_menu.css('left', 'auto');

            var targetOffset = Math.round(menu.offset().left + menu.outerWidth());
            var currentOffset = Math.round(autocomplete_container.offset().left + autocomplete_container.outerWidth());

            dropdown_menu.css('right', (currentOffset - targetOffset) + 'px');
        } else {
            /** Stick menu horizontally align on left to the input **/
            dropdown_menu.css('left', 'auto');
            dropdown_menu.css('right', '0px');
            autocomplete_container.addClass('reverse');

            var targetOffset = Math.round(menu.offset().left);
            var currentOffset = Math.round(autocomplete_container.offset().left);

            dropdown_menu.css('left', (targetOffset - currentOffset) + 'px');
        }
    };

    window.createISWidgetContainer = function (attributeName) {
        var div = document.createElement('div');
        div.className = 'is-widget-container-' + attributeName.split('.').join('_');
        div.dataset.attr = attributeName;

        return div;
    };

    // The url is now rendered as follows : http://website.com?q=searchquery&facet1=value&facet2=value1~value2
    // "?" and "&" are used to be fetched easily inside Magento for the backend rendering
    // Multivalued facets use "~" as separator
    // Targeted index is defined by sortBy parameter
    window.routing = {
        router: algoliaBundle.instantsearch.routers.history({
            parseURL: function (qsObject) {
                var location = qsObject.location,
                    qsModule = qsObject.qsModule;
                const queryString = location.hash ? location.hash : location.search;
                return qsModule.parse(queryString.slice(1))
            },
            createURL: function (qsObject) {
                var qsModule = qsObject.qsModule,
                    routeState = qsObject.routeState,
                    location = qsObject.location;
                const protocol = location.protocol,
                    hostname = location.hostname,
                    port = location.port ? location.port : '',
                    pathname = location.pathname,
                    hash = location.hash;

                const queryString = qsModule.stringify(routeState);
                const portWithPrefix = port === '' ? '' : ':' + port;
                // IE <= 11 has no location.origin or buggy. Therefore we don't rely on it
                if (!routeState || Object.keys(routeState).length === 0) {
                    return protocol + '//' + hostname + portWithPrefix + pathname;
                } else {
                    if (queryString && queryString != 'q=__empty__') {
                        return protocol + '//' + hostname + portWithPrefix + pathname + '?' + queryString;
                    } else {
                        return protocol + '//' + hostname + portWithPrefix + pathname;
                    }
                }
            },
        }),
        stateMapping: {
            stateToRoute: function (uiState) {
                var productIndexName = algoliaConfig.indexName + '_products';
                var uiStateProductIndex = uiState[productIndexName] || {};
                var routeParameters = {};
                if (algoliaConfig.isCategoryPage) {
                    routeParameters['q'] = uiState[productIndexName].query;
                } else if (algoliaConfig.isLandingPage) {
                    routeParameters['q'] = uiState[productIndexName].query || algoliaConfig.landingPage.query || '__empty__';
                } else {
                    routeParameters['q'] = uiState[productIndexName].query || algoliaConfig.request.query || '__empty__';
                }
                if (algoliaConfig.facets) {
                    for (var i = 0; i < algoliaConfig.facets.length; i++) {
                        var currentFacet = algoliaConfig.facets[i];
                        // Handle refinement facets
                        if (currentFacet.attribute != 'categories' && (currentFacet.type == 'conjunctive' || currentFacet.type == 'disjunctive')) {
                            routeParameters[currentFacet.attribute] = (uiStateProductIndex.refinementList &&
                                uiStateProductIndex.refinementList[currentFacet.attribute] &&
                                uiStateProductIndex.refinementList[currentFacet.attribute].join('~'));
                        }
                        // Handle categories
                        if (currentFacet.attribute == 'categories' && !algoliaConfig.isCategoryPage) {
                            routeParameters[currentFacet.attribute] = (uiStateProductIndex.hierarchicalMenu &&
                                uiStateProductIndex.hierarchicalMenu[currentFacet.attribute + '.level0'] &&
                                uiStateProductIndex.hierarchicalMenu[currentFacet.attribute + '.level0'].join('~'));
                        }
                        // Handle sliders
                        if (currentFacet.type == 'slider' || currentFacet.type == 'priceRanges') {
                            routeParameters[currentFacet.attribute] = (uiStateProductIndex.range &&
                                uiStateProductIndex.range[currentFacet.attribute] &&
                                uiStateProductIndex.range[currentFacet.attribute]);
                        }
                    }

                }
                routeParameters['sortBy'] = uiStateProductIndex.sortBy;
                routeParameters['page'] = uiStateProductIndex.page;
                return routeParameters;
            },
            routeToState: function (routeParameters) {
                var productIndexName = algoliaConfig.indexName + '_products';
                var uiStateProductIndex = {}

                uiStateProductIndex['query'] = routeParameters.q == '__empty__' ? '' : routeParameters.q;
                if (algoliaConfig.isLandingPage && typeof uiStateProductIndex['query'] === 'undefined' && algoliaConfig.landingPage.query != '') {
                    uiStateProductIndex['query'] = algoliaConfig.landingPage.query;
                }

                var landingPageConfig = algoliaConfig.isLandingPage && algoliaConfig.landingPage.configuration ?
                    JSON.parse(algoliaConfig.landingPage.configuration) :
                    {};

                uiStateProductIndex['refinementList'] = {};
                uiStateProductIndex['hierarchicalMenu'] = {};
                uiStateProductIndex['range'] = {};
                if (algoliaConfig.facets) {
                    for (var i = 0; i < algoliaConfig.facets.length; i++) {
                        var currentFacet = algoliaConfig.facets[i];
                        // Handle refinement facets
                        if (currentFacet.attribute != 'categories' && (currentFacet.type == 'conjunctive' || currentFacet.type == 'disjunctive')) {
                            uiStateProductIndex['refinementList'][currentFacet.attribute] = routeParameters[currentFacet.attribute] && routeParameters[currentFacet.attribute].split('~');
                            if (algoliaConfig.isLandingPage &&
                                typeof uiStateProductIndex['refinementList'][currentFacet.attribute] === 'undefined' &&
                                currentFacet.attribute in landingPageConfig) {
                                uiStateProductIndex['refinementList'][currentFacet.attribute] = landingPageConfig[currentFacet.attribute].split('~');
                            }
                        }
                        // Handle categories facet
                        if (currentFacet.attribute == 'categories' && !algoliaConfig.isCategoryPage) {
                            uiStateProductIndex['hierarchicalMenu']['categories.level0'] = routeParameters['categories'] && routeParameters['categories'].split('~');
                            if (algoliaConfig.isLandingPage &&
                                typeof uiStateProductIndex['hierarchicalMenu']['categories.level0'] === 'undefined' &&
                                'categories.level0' in landingPageConfig) {
                                uiStateProductIndex['hierarchicalMenu']['categories.level0'] = landingPageConfig['categories.level0'].split(' /// ');
                            }
                        }
                        if (currentFacet.attribute == 'categories' && algoliaConfig.isCategoryPage) {
                            uiStateProductIndex['hierarchicalMenu']['categories.level0'] = [algoliaConfig.request.path];
                        }
                        // Handle sliders
                        if (currentFacet.type == 'slider' || currentFacet.type == 'priceRanges') {
                            var currentFacetAttribute = currentFacet.attribute;
                            uiStateProductIndex['range'][currentFacetAttribute] = routeParameters[currentFacetAttribute] && routeParameters[currentFacetAttribute];
                            if (algoliaConfig.isLandingPage &&
                                typeof uiStateProductIndex['range'][currentFacetAttribute] === 'undefined' &&
                                currentFacetAttribute in landingPageConfig) {

                                var facetValue = '';
                                if (typeof landingPageConfig[currentFacetAttribute]['>='] !== "undefined") {
                                    facetValue = landingPageConfig[currentFacetAttribute]['>='][0];
                                }
                                facetValue += ':';
                                if (typeof landingPageConfig[currentFacetAttribute]['<='] !== "undefined") {
                                    facetValue += landingPageConfig[currentFacetAttribute]['<='][0];
                                }
                                uiStateProductIndex['range'][currentFacetAttribute] = facetValue;
                            }
                        }
                    }

                }
                uiStateProductIndex['sortBy'] = routeParameters.sortBy;
                uiStateProductIndex['page'] = routeParameters.page;

                var uiState = {};
                uiState[productIndexName] = uiStateProductIndex;
                return uiState;
            }
        }
    };

    // Taken from Magento's tools.js - not included on frontend, only in backend
    window.AlgoliaBase64 = {
        // private property
        _keyStr: "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
        //'+/=', '-_,'
        // public method for encoding
        encode: function (input) {
            var output = "";
            var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
            var i = 0;

            if (typeof window.btoa === "function") {
                return window.btoa(input);
            }

            input = AlgoliaBase64._utf8_encode(input);

            while (i < input.length) {

                chr1 = input.charCodeAt(i++);
                chr2 = input.charCodeAt(i++);
                chr3 = input.charCodeAt(i++);

                enc1 = chr1 >> 2;
                enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
                enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
                enc4 = chr3 & 63;

                if (isNaN(chr2)) {
                    enc3 = enc4 = 64;
                } else if (isNaN(chr3)) {
                    enc4 = 64;
                }
                output = output +
                    this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
                    this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);
            }

            return output;
        },

        // public method for decoding
        decode: function (input) {
            var output = "";
            var chr1, chr2, chr3;
            var enc1, enc2, enc3, enc4;
            var i = 0;

            if (typeof window.atob === "function") {
                return window.atob(input);
            }

            input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

            while (i < input.length) {

                enc1 = this._keyStr.indexOf(input.charAt(i++));
                enc2 = this._keyStr.indexOf(input.charAt(i++));
                enc3 = this._keyStr.indexOf(input.charAt(i++));
                enc4 = this._keyStr.indexOf(input.charAt(i++));

                chr1 = (enc1 << 2) | (enc2 >> 4);
                chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
                chr3 = ((enc3 & 3) << 6) | enc4;

                output = output + String.fromCharCode(chr1);

                if (enc3 !== 64) {
                    output = output + String.fromCharCode(chr2);
                }
                if (enc4 !== 64) {
                    output = output + String.fromCharCode(chr3);
                }
            }
            output = AlgoliaBase64._utf8_decode(output);
            return output;
        },

        mageEncode: function (input) {
            return this.encode(input).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, ',');
        },

        mageDecode: function (output) {
            output = output.replace(/\-/g, '+').replace(/_/g, '/').replace(/,/g, '=');
            return this.decode(output);
        },

        idEncode: function (input) {
            return this.encode(input).replace(/\+/g, ':').replace(/\//g, '_').replace(/=/g, '-');
        },

        idDecode: function (output) {
            output = output.replace(/\-/g, '=').replace(/_/g, '/').replace(/\:/g, '\+');
            return this.decode(output);
        },

        // private method for UTF-8 encoding
        _utf8_encode: function (string) {
            string = string.replace(/\r\n/g, "\n");
            var utftext = "";

            for (var n = 0; n < string.length; n++) {

                var c = string.charCodeAt(n);

                if (c < 128) {
                    utftext += String.fromCharCode(c);
                } else if ((c > 127) && (c < 2048)) {
                    utftext += String.fromCharCode((c >> 6) | 192);
                    utftext += String.fromCharCode((c & 63) | 128);
                } else {
                    utftext += String.fromCharCode((c >> 12) | 224);
                    utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                    utftext += String.fromCharCode((c & 63) | 128);
                }
            }
            return utftext;
        },

        // private method for UTF-8 decoding
        _utf8_decode: function (utftext) {
            var string = "";
            var i = 0;
            var c = c1 = c2 = 0;

            while (i < utftext.length) {

                c = utftext.charCodeAt(i);

                if (c < 128) {
                    string += String.fromCharCode(c);
                    i++;
                } else if ((c > 191) && (c < 224)) {
                    c2 = utftext.charCodeAt(i + 1);
                    string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
                    i += 2;
                } else {
                    c2 = utftext.charCodeAt(i + 1);
                    c3 = utftext.charCodeAt(i + 2);
                    string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                    i += 3;
                }
            }
            return string;
        }
    };


    $(function ($) {
        if (typeof algoliaConfig === 'undefined') {
            return;
        }
        $(algoliaConfig.autocomplete.selector).each(function () {
            $(this).closest('form').on('submit', function (e) {
                let query = $(this).find(algoliaConfig.autocomplete.selector).val();

                query = encodeURIComponent(query);

                if (algoliaConfig.instant.enabled && query === '')
                    query = '__empty__';

                window.location = $(this).attr('action') + '?q=' + query;

                return false;
            });
        });

        function handleInputCrossAutocomplete(input) {
            if (input.val().length > 0) {
                input.closest('#algolia-searchbox').find('.clear-query-autocomplete').show();
                input.closest('#algolia-searchbox').find('.magnifying-glass').hide();
            } else {
                input.closest('#algolia-searchbox').find('.clear-query-autocomplete').hide();
                input.closest('#algolia-searchbox').find('.magnifying-glass').show();
            }
        }

        $(document).on('click', '.clear-query-autocomplete', function () {
            var input = $(this).closest('#algolia-searchbox').find('input');

            input.val('');
            input.get(0).dispatchEvent(new Event('input'));

            handleInputCrossAutocomplete(input);
        });

        /** Handle small screen **/
        $('body').on('click', '#refine-toggle', function () {
            $('#instant-search-facets-container').toggleClass('hidden-sm').toggleClass('hidden-xs');
            if ($(this).html().trim()[0] === '+')
                $(this).html('- ' + algoliaConfig.translations.refine);
            else
                $(this).html('+ ' + algoliaConfig.translations.refine);
        });


    });

});
