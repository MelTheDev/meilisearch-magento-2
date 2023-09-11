define([], function () {
    return {

        ////////////////////
        //  Template API  //
        ////////////////////

        getNoResultHtml: function ({html}) {
            return html`<p>${algoliaConfig.translations.noResults}</p>`;
        },

        getHeaderHtml: function () {
            return "";
        },

        getItemHtml: function ({item, components, html}) {
            return html`<a class="algoliasearch-autocomplete-hit"
                           href="${item.__autocomplete_queryID != null ? item.urlForInsights : item.url}"
                           data-objectId="${item.objectID}"
                           data-position="${item.position}"
                           data-index="${item.__autocomplete_indexName}"
                           data-queryId="${item.__autocomplete_queryID}">
                <div class="thumb"><img src="${item.thumbnail_url || ''}" alt="${item.name || ''}"/></div>
                <div class="info">
                    ${components.Highlight({hit: item, attribute: 'name'})}
                    <div class="algoliasearch-autocomplete-category">
                        ${this.getColorHtml(item, components, html)}
                        ${this.getCategoriesHtml(item, components, html)}
                    </div>

                    ${this.getPricingHtml(item, html)}
                </div>
            </a>`;
        },

        getFooterHtml: function ({html, ...resultDetails}) {
            return html`<div id="autocomplete-products-footer">
                ${this.getFooterSearchLinks(html, resultDetails)}
            </div>`;
        },

        ////////////////////
        // Helper methods //
        ////////////////////

        getColorHtml: (item, components, html) => {
            if (item._highlightResult.color == undefined || item._highlightResult.color.value == "") return "";

            return html`<span class="color">color: ${components.Highlight({ hit: item, attribute: "color" })}</span>`;
        },

        getCategoriesHtml: (item, components, html) => {
            if (item.categories_without_path == undefined || item.categories_without_path.length == 0) return "";

            return html`<span>in ${components.Highlight({ hit: item, attribute: "categories_without_path",})}</span>`;
        },

        getOriginalPriceHtml: (item, html, priceGroup) => {
            if (item['price'][algoliaConfig.currencyCode][priceGroup + '_original_formated'] == null) return "";

            return html`<span class="before_special"> ${item['price'][algoliaConfig.currencyCode][priceGroup + '_original_formated']} </span>`;
        },

        getTierPriceHtml: (item, html, priceGroup) => {
            if (item['price'][algoliaConfig.currencyCode][priceGroup + '_tier_formated'] == null) return "";

            return html`<span class="tier_price"> As low as <span class="tier_value">${item['price'][algoliaConfig.currencyCode][priceGroup + '_tier_formated']}</span></span>`;
        },

        getPricingHtml: function(item, html) {
            if (item['price'] == undefined) return "";

            const priceGroup =  algoliaConfig.priceGroup || 'default';

            return html `<div className="algoliasearch-autocomplete-price">
                <span className="after_special ${algoliaConfig.origFormatedVar != null ? 'promotion' : ''}">
                    ${item['price'][algoliaConfig.currencyCode][priceGroup + '_formated']}
                </span>
                ${this.getOriginalPriceHtml(item, html, priceGroup)}

                ${this.getTierPriceHtml(item, html, priceGroup)}
            </div>`;
        },

        getFooterSearchCategoryLinks: (html, resultDetails) => {
            if (resultDetails.allCategories == undefined || resultDetails.allCategories.length === 0) return "";

            return html ` ${algoliaConfig.translations.orIn}
                ${resultDetails.allCategories.map((list, index) =>
                    index === 0 ? html` <span><a href="${list.url}">${list.name}</a></span>` : html`, <span><a href="${list.url}">${list.name}</a></span>`
                )}
            `;
        },

        getFooterSearchLinks: function(html, resultDetails)  {
            if (resultDetails.nbHits === 0) return "";

            return html`${algoliaConfig.translations.seeIn} <span><a href="${resultDetails.allDepartmentsUrl}">${algoliaConfig.translations.allDepartments}</a></span> (${resultDetails.nbHits})
                ${this.getFooterSearchCategoryLinks(html, resultDetails)}
            `;
        }

    };
});
