<?php

namespace MelTheDev\MeiliSearch\Helper\Entity;

use Magento\Cms\Model\Page;
use MelTheDev\MeiliSearch\Helper\ConfigHelper;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\UrlFactory;
use Magento\Store\Model\StoreManagerInterface;

class PageHelper
{
    public function __construct(
        private ManagerInterface $eventManager,
        private PageCollectionFactory $pageCollectionFactory,
        private ConfigHelper $configHelper,
        private FilterProvider $filterProvider,
        private StoreManagerInterface $storeManager,
        private UrlFactory $frontendUrlFactory
    ) {
    }

    public function getIndexNameSuffix()
    {
        return '_pages';
    }

    public function getPages($storeId, array $pageIds = null)
    {
        /** @var \Magento\Cms\Model\ResourceModel\Page\Collection $magentoPages */
        $magentoPages = $this->pageCollectionFactory->create()
            ->addStoreFilter($storeId)
            ->addFieldToFilter('is_active', 1);

        if ($pageIds && count($pageIds)) {
            $magentoPages->addFieldToFilter('page_id', ['in' => $pageIds]);
        }

        $excludedPages = $this->getExcludedPageIds();
        if (count($excludedPages)) {
            $magentoPages->addFieldToFilter('identifier', ['nin' => $excludedPages]);
        }

        $pageIdsToRemove = $pageIds ? array_flip($pageIds) : [];

        $pages = [];

        $frontendUrlBuilder = $this->frontendUrlFactory->create()->setScope($storeId);

        /** @var Page $page */
        foreach ($magentoPages as $page) {
            $pageObject = [];

            $pageObject['slug'] = $page->getIdentifier();
            $pageObject['name'] = $page->getTitle();

            $page->setData('store_id', $storeId);

            if (!$page->getId()) {
                continue;
            }

            $content = $page->getContent();
            if ($this->configHelper->getRenderTemplateDirectives()) {
                $content = $this->filterProvider->getPageFilter()->filter($content);
            }

            $pageObject['objectID'] = $page->getId();
            $pageObject['url'] = $frontendUrlBuilder->getUrl(
                null,
                [
                    '_direct' => $page->getIdentifier(),
                    '_secure' => $this->configHelper->useSecureUrlsInFrontend($storeId),
                ]
            );
            $pageObject['content'] = $this->strip($content, ['script', 'style']);

            $transport = new DataObject($pageObject);
           /* $this->eventManager->dispatch(
                'melthedev_meilisearch_after_create_page_object',
                ['page' => $transport, 'pageObject' => $page]
            );*/
            $pageObject = $transport->getData();

            if (isset($pageIdsToRemove[$page->getId()])) {
                unset($pageIdsToRemove[$page->getId()]);
            }
            $pages['toIndex'][] = $pageObject;
        }

        $pages['toRemove'] = array_unique(array_keys($pageIdsToRemove));

        return $pages;
    }

    /**
     * @param $s
     * @param $completeRemoveTags
     * @return string
     */
    private function strip($s, $completeRemoveTags = [])
    {
        if ($completeRemoveTags && $completeRemoveTags !== [] && $s) {
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $encodedStr = mb_encode_numericentity($s, [0x80, 0x10fffff, 0, ~0]);
            $dom->loadHTML($encodedStr);
            libxml_use_internal_errors(false);

            $toRemove = [];
            foreach ($completeRemoveTags as $tag) {
                $removeTags = $dom->getElementsByTagName($tag);

                foreach ($removeTags as $item) {
                    $toRemove[] = $item;
                }
            }

            foreach ($toRemove as $item) {
                $item->parentNode->removeChild($item);
            }

            $s = $dom->saveHTML();
        }

        $s = html_entity_decode($s, 0, 'UTF-8');

        $s = trim(preg_replace('/\s+/', ' ', $s));
        $s = preg_replace('/&nbsp;/', ' ', $s);
        $s = preg_replace('!\s+!', ' ', $s);
        $s = preg_replace('/\{\{[^}]+\}\}/', ' ', $s);
        $s = strip_tags($s);
        $s = trim($s);

        return $s;
    }

    public function getIndexSettings($storeId)
    {
        $indexSettings = [
            //'searchableAttributes' => ['unordered(slug)', 'unordered(name)', 'unordered(content)'],
            'searchableAttributes' => ['slug', 'name', 'content'],
            //'attributesToSnippet'  => ['content:7'],
        ];

        $transport = new DataObject($indexSettings);
        /*$this->eventManager->dispatch(
            'melthedev_meilisearch_pages_index_before_set_settings',
            ['store_id' => $storeId, 'index_settings' => $transport]
        );*/
        $indexSettings = $transport->getData();
        return $indexSettings;
    }

    public function getStores($storeId = null)
    {
        $storeIds = [];

        if ($storeId === null) {
            /** @var \Magento\Store\Model\Store $store */
            foreach ($this->storeManager->getStores() as $store) {
                if ($this->configHelper->isEnabledBackEnd($store->getId()) === false) {
                    continue;
                }

                if ($store->getData('is_active')) {
                    $storeIds[] = $store->getId();
                }
            }
        } else {
            $storeIds = [$storeId];
        }

        return $storeIds;
    }

    /**
     * Get excluded page ids
     *
     * @return array
     */
    public function getExcludedPageIds()
    {
        $excludedPages = array_values($this->configHelper->getExcludedPages());
        foreach ($excludedPages as &$excludedPage) {
            $excludedPage = $excludedPage['attribute'];
        }
        return $excludedPages;
    }
}
