<?php

namespace centrillion\extendifyforcraftcms\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\updates\UpdatesAsset;

/**
 * Sherlock Asset bundle
 */
class ExtendifyAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@centrillion/extendifyforcraftcms/resources';

        $this->depends = [
            CpAsset::class,
            UpdatesAsset::class,
        ];

        // $this->css = [
        //     'css/cp.css',
        //     'lib/font-awesome/css/all.min.css',
        // ];
        // $this->js = [
        //     'js/script.js',
        // ];

        parent::init();
    }
}