var config = {
    map   : {
        '*': {
            // Magento FE libs
            'algoliaCommon'       : 'MelTheDev_MeiliSearch/internals/common',
            'algoliaAutocomplete' : 'MelTheDev_MeiliSearch/autocomplete',
            'algoliaInstantSearch': 'MelTheDev_MeiliSearch/instantsearch',
            'algoliaInsights'     : 'MelTheDev_MeiliSearch/insights',
            'algoliaHooks'        : 'MelTheDev_MeiliSearch/hooks',

            // Autocomplete templates
            'productsHtml'   : 'MelTheDev_MeiliSearch/internals/template/autocomplete/products',
            'pagesHtml'      : 'MelTheDev_MeiliSearch/internals/template/autocomplete/pages',
            'categoriesHtml' : 'MelTheDev_MeiliSearch/internals/template/autocomplete/categories',
            'suggestionsHtml': 'MelTheDev_MeiliSearch/internals/template/autocomplete/suggestions',
            'additionalHtml' : 'MelTheDev_MeiliSearch/internals/template/autocomplete/additional-section',

            // Recommend templates
            'recommendProductsHtml': 'MelTheDev_MeiliSearch/internals/template/recommend/products'
        }
    },
    paths : {
        'algoliaBundle'   : 'MelTheDev_MeiliSearch/internals/algoliaBundle.min',
        'polyFillBundle' : 'MelTheDev_MeiliSearch/internals/polyfill',
        'meiliSearchBundle': 'MelTheDev_MeiliSearch/internals/instant-meilisearch',
        'meiliSearchAutocomplete': 'MelTheDev_MeiliSearch/internals/meilisearch-autocomplete',
        'algoliaAnalytics': 'MelTheDev_MeiliSearch/internals/search-insights',
        'recommend'       : 'MelTheDev_MeiliSearch/internals/recommend.min',
        'recommendJs'     : 'MelTheDev_MeiliSearch/internals/recommend-js.min',
        'rangeSlider'     : 'MelTheDev_MeiliSearch/navigation/range-slider-widget',
    },
    deps  : [
        'algoliaInstantSearch',
        'algoliaInsights'
    ],
    config: {
        mixins: {
            'Magento_Catalog/js/catalog-add-to-cart': {
                'MelTheDev_MeiliSearch/insights/add-to-cart-mixin': true
            },
        }
    }
};
