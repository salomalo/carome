<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit4a635eab7ca2ff84b1c820c0b8244ec3
{
    public static $prefixesPsr0 = array (
        'x' => 
        array (
            'xrstf\\Composer52' => 
            array (
                0 => __DIR__ . '/..' . '/xrstf/composer-php52/lib',
            ),
        ),
    );

    public static $classMap = array (
        'WPML_ACF' => __DIR__ . '/../..' . '/classes/class-wpml-acf.php',
        'WPML_ACF_Attachments' => __DIR__ . '/../..' . '/classes/class-wpml-acf-attachments.php',
        'WPML_ACF_Convertable' => __DIR__ . '/../..' . '/classes/wpml-acf-convertable.php',
        'WPML_ACF_Custom_Fields_Sync' => __DIR__ . '/../..' . '/classes/class-wpml-acf-custom-fields-sync.php',
        'WPML_ACF_Display_Translated' => __DIR__ . '/../..' . '/classes/class-wpml-acf-display-translated.php',
        'WPML_ACF_Duplicated_Post' => __DIR__ . '/../..' . '/classes/class-wpml-acf-duplicated-post.php',
        'WPML_ACF_Editor_Hooks' => __DIR__ . '/../..' . '/classes/class-wpml-acf-editor-hooks.php',
        'WPML_ACF_Field' => __DIR__ . '/../..' . '/classes/class-wpml-acf-field.php',
        'WPML_ACF_Field_Annotations' => __DIR__ . '/../..' . '/classes/class-wpml-acf-field-annotations.php',
        'WPML_ACF_Location_Rules' => __DIR__ . '/../..' . '/classes/class-wpml-acf-location-rules.php',
        'WPML_ACF_Page_Link_Field' => __DIR__ . '/../..' . '/classes/class-wpml-acf-page-link-field.php',
        'WPML_ACF_Post_Id' => __DIR__ . '/../..' . '/classes/class-wpml-acf-post-id.php',
        'WPML_ACF_Post_Ids' => __DIR__ . '/../..' . '/classes/class-wpml-acf-post-ids.php',
        'WPML_ACF_Post_Object_Field' => __DIR__ . '/../..' . '/classes/class-wpml-acf-post-object-field.php',
        'WPML_ACF_Pro' => __DIR__ . '/../..' . '/classes/class-wpml-acf-pro.php',
        'WPML_ACF_Processed_Data' => __DIR__ . '/../..' . '/classes/class-wpml-acf-processed-data.php',
        'WPML_ACF_Relationship_Field' => __DIR__ . '/../..' . '/classes/class-wpml-acf-relationship-field.php',
        'WPML_ACF_Repeater_Field' => __DIR__ . '/../..' . '/classes/class-wpml-acf-repeater-field.php',
        'WPML_ACF_Requirements' => __DIR__ . '/../..' . '/classes/class-wpml-acf-requirements.php',
        'WPML_ACF_Taxonomy_Field' => __DIR__ . '/../..' . '/classes/class-wpml-acf-taxonomy-field.php',
        'WPML_ACF_Term_Id' => __DIR__ . '/../..' . '/classes/class-wpml-acf-term-id.php',
        'WPML_ACF_Term_Ids' => __DIR__ . '/../..' . '/classes/class-wpml-acf-term-ids.php',
        'WPML_ACF_Void_Field' => __DIR__ . '/../..' . '/classes/class-wpml-acf-void-field.php',
        'WPML_ACF_Worker' => __DIR__ . '/../..' . '/classes/class-wpml-acf-worker.php',
        'WPML_ACF_Xliff' => __DIR__ . '/../..' . '/classes/class-wpml-acf-xliff.php',
        'xrstf\\Composer52\\AutoloadGenerator' => __DIR__ . '/..' . '/xrstf/composer-php52/lib/xrstf/Composer52/AutoloadGenerator.php',
        'xrstf\\Composer52\\Generator' => __DIR__ . '/..' . '/xrstf/composer-php52/lib/xrstf/Composer52/Generator.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixesPsr0 = ComposerStaticInit4a635eab7ca2ff84b1c820c0b8244ec3::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit4a635eab7ca2ff84b1c820c0b8244ec3::$classMap;

        }, null, ClassLoader::class);
    }
}
