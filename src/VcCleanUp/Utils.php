<?php namespace DigitalUnited\VcCleanUp;

class Utils
{
  protected $stdModules;
  protected $whiteListedModules;

  function __construct($stdModules, $config)
  {
    $this->stdModules = $stdModules;
    $this->whiteListedModules = $config['enabledStdModules'];
  }

  /**
   * Removes all standard modules witch is not a port of the array $whiteList
   * @param array $whiteList (Standard modules to keep)
   */
  public function enabledStdModules(Array $whiteList)
  {
    foreach ($this->stdModules as $moduleName) {
      if (!in_array($moduleName, $whiteList)) {
        vc_remove_element($moduleName);
      }
    }
  }

  /**
   *  Replaces "vc_"-prefiexd css classes in the with bootstraps grid classes if $param equals true
   * @param $param
   */
  public function useBootstrapGrid($param)
  {
    if ($param) {
      add_filter('vc_shortcodes_css_class', function ($class_string, $tag) {

        $tags_to_clean = [
          'vc_row',
          'vc_column',
          'vc_row_inner',
          'vc_column_inner',
        ];

        if (in_array($tag, $tags_to_clean)) {
          //Remove unused VC classes
          $class_string = str_replace(' wpb_row', '', $class_string);
          $class_string = str_replace(' vc_row-fluid', '', $class_string);
          $class_string = str_replace(' vc_column_container', '', $class_string);

          //Remove all vc_prefixes
          $class_string = str_replace('vc_', '', $class_string);
        }

        $class_string = preg_replace('|col-sm|', 'col-sm', $class_string);

        return $class_string;
      }, 10, 2);
    }
  }

  /**
   * Disables loading of visualcomposaer css if $param equals true
   * @param $param
   */
  public function deregisterFrontendStyles($param)
  {
    if (!$param) {
      return;
    }

    add_action('init', function () {
      //Don't deregister VCs CSS when using the frontend editor
      if (in_array(vc_mode(), ['page_editable', 'admin_page', 'admin_frontend_editor'])) {
        return;
      }

      add_action('wp_enqueue_scripts', function () {
        wp_deregister_style('js_composer_front');
      });
    });
  }

  /**
   * Disables frontendeditor of if $param equals true
   * @param $param
   */
  public function disableFrontendEditor($param)
  {
    if ($param) {
      vc_disable_frontend();
    }
  }

  /**
   * Removes the "Extra class name"-field on all white listed modules if $param is true
   * @param $param
   */
  public function removeExtraClassNameField($param)
  {
    if ($param) {
      add_action('init', function () {
        foreach ($this->whiteListedModules as $moduleName) {
          vc_remove_param($moduleName, 'el_class');
        }
      });
    }
  }

  /**
   * Removes the "Design options"-tab on all white listed modules if $param is true
   * @param $param
   */
  public function removeDesignOptionsTab($param)
  {
    if ($param) {
      add_action('init', function () {
        foreach ($this->whiteListedModules as $moduleName) {
          vc_remove_param($moduleName, 'css');
        }
      });
    }
  }

  /**
   * Removes all row layouts that isnt in the array $rowLayoutsToKeep or
   * @param array|bool $rowLayoutsToKeep
   */
  public function enableRowLayouts($rowLayoutsToKeep)
  {
    if (is_array($rowLayoutsToKeep)) {
      add_action('vc_after_init_base', function () use ($rowLayoutsToKeep) {
        global $vc_row_layouts;

        foreach ($vc_row_layouts as $key => $vcRowLayout) {
          if (false === array_search($vcRowLayout['title'], $rowLayoutsToKeep)) {
            unset($vc_row_layouts[$key]);
          }
        }
      });
    }
  }

  /**
   * Set vc as a part of a theme witch disables the "Custom CSS" tab on the Visual Composer options-page if $param equals true
   * @param $param
   */
  public function setVcAsTheme($param)
  {
    if ($param) {
      add_action('vc_before_init', function () {
        vc_set_as_theme();
      });
    }
  }

  /**
   * Removes Grid Elements from the admin menu if $param equals true
   * @param $param
   */
  public function disableGridElements($param)
  {
    if ($param) {
      add_action('admin_menu', function () {
        remove_menu_page('edit.php?post_type=vc_grid_item');
        remove_menu_page('vc-welcome');
      });

      add_action('admin_bar_menu', function ($wp_admin_bar) {
        $wp_admin_bar->remove_node('new-vc_grid_item');
      }, 999);
    }
  }

  /**
   * Keep all templates withe the custom_class in array $templatesToKeep
   * @param $templatesToKeep
   */
  public function keepDefaultTemplates($templatesToKeep)
  {
    add_filter('vc_load_default_templates', function ($templates) use ($templatesToKeep) {
      $returnArray = [];
      if (is_array($templatesToKeep) && count($templatesToKeep) > 0) {
        $returnArray = array_filter($templates, function ($var) use ($templatesToKeep) {
          return in_array($var['custom_class'], $templatesToKeep);
        });
      }

      return $returnArray;
    });
  }

  /**
   * Removes alot of unusefull buttons from VisualComposers admin GUI if $param equals true
   * @param $param
   */
  public function hideVcAdminButtons($param)
  {
    if ($param) {
      add_action('admin_head', function () {
        echo '
                    <style type="text/css">
                        .vc_control.column_toggle.vc_column-toggle, /* Hides add column and toggle row */
                        #vc_post-settings-button,
                        #vc_logo
                        {
                            display:none; visibility: hidden;
                        }
                        /* Hides logo */
                        .vc_navbar .vc_navbar-header
                        {
                            display:block; visibility: visible;
                        }
                    </style>
                ';
      });
    }
  }

  public function keepDefaultColumnFields($paramsToKeep)
  {
    if (!is_array($paramsToKeep) || $paramsToKeep === 'all') {
      return;
    }
    add_action('init', function () use ($paramsToKeep) {
      $vcColumnPrevious = \WPBMap::getShortCode('vc_column');
      //Exit early if we don't have any params
      if (empty($vcColumnPrevious['params'])) {
        return;
      }

      // Fill $newColumnSettings with the kept params of the column
      $newColumnSettings = ['params' => []];
      foreach ($vcColumnPrevious['params'] as $key => $param) {
        if (in_array($param['param_name'], $paramsToKeep)) {
          $newColumnSettings['params'][] = $param;
        }
      }

      //Update vc_column with the new params
      vc_map_update('vc_column', $newColumnSettings);
    });
  }

  public function removeDeprecatedNoticeOnModules($param)
  {
    if ($param) {
      add_action('vc_after_init', function () {
        vc_map_update('vc_tabs', [
          'deprecated' => false,
        ]);

        vc_map_update('vc_accordion', [
          'deprecated' => false,
        ]);

        vc_map_update('vc_tour', [
          'deprecated' => false,
        ]);
      });
    }
  }

  public static function setVCAsDefaultEditorForPostTypes($postTypes)
  {
    if (!empty($postTypes) && function_exists('vc_set_default_editor_post_types')) {
      vc_set_default_editor_post_types($postTypes);
    }
  }

  public static function disableFrontJS($param)
  {
    if ($param) {
      add_action('wp_enqueue_scripts', function () {
        wp_deregister_script('wpb_composer_front_js');
      });
    }
  }
}
