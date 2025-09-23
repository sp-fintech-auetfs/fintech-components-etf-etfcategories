<?php

namespace Apps\Fintech\Components\Etf\Categories;

use Apps\Fintech\Packages\Adminltetags\Traits\DynamicTable;
use Apps\Fintech\Packages\Etf\Categories\EtfCategories;
use System\Base\BaseComponent;

class CategoriesComponent extends BaseComponent
{
    use DynamicTable;

    protected $categoriesPackage;

    protected $categories = [];

    public function initialize()
    {
        $this->categoriesPackage = $this->usePackage(EtfCategories::class);
    }

    /**
     * @acl(name=view)
     */
    public function viewAction()
    {
        if (isset($this->getData()['id'])) {
            $categories = $this->categoriesPackage->getAll()->etfcategories;

            $parents = [];

            foreach ($categories as $categoryId => $category) {
                if (!is_null($category['parent_id'])) {
                    $parents[$categoryId] = $category;
                }
            }

            $this->view->parents = $parents;

            if ($this->getData()['id'] != 0) {
                $category = $this->categoriesPackage->getById((int) $this->getData()['id']);

                if (!$category) {
                    return $this->throwIdNotFound();
                }

                if (!isset($category['turn_around_time'])) {
                    if (isset($category['parent_id'])) {
                        if (!isset($this->categories[$category['parent_id']])) {
                            $parent = $this->categories[$category['parent_id']] = $this->categoriesPackage->getById((int) $category['parent_id']);
                        } else {
                            $parent = $this->categories[$category['parent_id']];
                        }

                        if ($parent && isset($parent['turn_around_time'])) {
                            $category['turn_around_time'] = $parent['turn_around_time'];
                        } else {
                            $category['turn_around_time'] = '-';
                        }
                    } else {
                        $category['turn_around_time'] = '-';
                    }
                }
                $this->view->category = $category;
            }

            $this->view->pick('categories/view');

            return;
        }

        $controlActions =
            [
                // 'disableActionsForIds'  => [1],
                'actionsToEnable'       =>
                [
                    'edit'      => 'etf/categories',
                    'remove'    => 'etf/categories/remove'
                ]
            ];

        $replaceColumns =
            function ($dataArr) {
                if ($dataArr && is_array($dataArr) && count($dataArr) > 0) {
                    return $this->replaceColumns($dataArr);
                }

                return $dataArr;
            };

        $this->generateDTContent(
            $this->categoriesPackage,
            'etf/categories/view',
            null,
            ['name', 'parent_id', 'turn_around_time'],
            true,
            ['name', 'parent_id', 'turn_around_time'],
            $controlActions,
            ['parent_id' => 'Parent', 'turn_around_time' => 'Turn Around Time (Days)'],
            $replaceColumns,
            'name'
        );

        $this->view->pick('categories/list');
    }

    protected function replaceColumns($dataArr)
    {
        foreach ($dataArr as $dataKey => &$data) {
            $data = $this->formatTurnAroundTime($dataKey, $data);
            $data = $this->formatParent($dataKey, $data);
        }

        return $dataArr;
    }

    protected function formatTurnAroundTime($rowId, $data)
    {
        if (!isset($data['turn_around_time'])) {
            if (isset($data['parent_id'])) {
                if (!isset($this->categories[$data['parent_id']])) {
                    $parent = $this->categories[$data['parent_id']] = $this->categoriesPackage->getById((int) $data['parent_id']);
                } else {
                    $parent = $this->categories[$data['parent_id']];
                }

                if ($parent && isset($parent['turn_around_time'])) {
                    $data['turn_around_time'] = $parent['turn_around_time'];
                } else {
                    $data['turn_around_time'] = '-';
                }
            } else {
                $data['turn_around_time'] = '-';
            }
        }

        return $data;
    }

    protected function formatParent($rowId, $data)
    {
        if ($data['parent_id']) {
            if (!isset($this->categories[$data['parent_id']])) {
                $parent = $this->categories[$data['parent_id']] = $this->categoriesPackage->getById((int) $data['parent_id']);
            } else {
                $parent = $this->categories[$data['parent_id']];
            }

            $data['parent_id'] = $parent['name'];
        } else {
            $data['parent_id'] = '-';
        }

        return $data;
    }

    /**
     * @acl(name=add)
     */
    public function addAction()
    {
        //
    }

    /**
     * @acl(name=update)
     */
    public function updateAction()
    {
        $this->requestIsPost();

        $this->categoriesPackage->updateEtfCategories($this->postData());

        $this->addResponse(
            $this->categoriesPackage->packagesData->responseMessage,
            $this->categoriesPackage->packagesData->responseCode
        );
    }

    /**
     * @acl(name=remove)
     */
    public function removeAction()
    {
        //
    }

    public function calculateCategoriesPercentDiffAction()
    {
        $this->requestIsPost();

        if (!isset($this->postData()['mainCategory']) ||
            !isset($this->postData()['withCategory'])
        ) {
            $this->addResponse('Please provide main and with categories', 1);

            return false;
        }

        $this->categoriesPackage->calculateCategoriesPercentDiff($this->postData()['mainCategory'], $this->postData()['withCategory']);

        $this->addResponse(
            $this->categoriesPackage->packagesData->responseMessage,
            $this->categoriesPackage->packagesData->responseCode,
            $this->categoriesPackage->packagesData->responseData ?? []
        );
    }

    public function getCategoryTurnAroundTimeAction()
    {
        $this->requestIsPost();

        if (!isset($this->postData()['category_id'])) {
            $this->addResponse('Please provide category ID', 1);

            return false;
        }

        $this->categoriesPackage->getCategoryTurnAroundTime($this->postData()['category_id']);

        $this->addResponse(
            $this->categoriesPackage->packagesData->responseMessage,
            $this->categoriesPackage->packagesData->responseCode,
            $this->categoriesPackage->packagesData->responseData ?? []
        );
    }
}