define([], function () {
    return {
        getNoResultHtml: function ({html}) {
            return html`<p>${algoliaConfig.translations.noResults}</p>`;
        },

        getHeaderHtml: function ({section}) {
            return section.label;
        },

        getItemHtml: function ({item, components, html}) {
            return html`<a class="algoliasearch-autocomplete-hit"
                           href="${item.url}"
                           data-objectId="${item.objectID}"
                           data-position="${item.position}"
                           data-index="${item.__autocomplete_indexName}"
                           data-queryId="${item.__autocomplete_queryID}">
                <div class="info-without-thumb">
                    ${components.Highlight({hit: item, attribute: 'name'})}
                    <div class="details">
                        ${components.Highlight({hit: item, attribute: 'content'})}
                    </div>
                </div>
                <div class="algolia-clearfix"></div>
            </a>`;
        },

        getFooterHtml: function () {
            return "";
        }
    };
});
