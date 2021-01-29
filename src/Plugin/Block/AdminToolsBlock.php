<?php


namespace Drupal\company_page\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'My Template' block.
 *
 * @Block(
 *   id = "admin_tools",
 *   admin_label = @Translation("Admin Tools - Company Page"),
 *   category = @Translation("Custom")
 * )
 */
class AdminToolsBlock extends BlockBase
{

  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function build(){

    return [
      '#theme' => 'admin_tools',
      '#test_var' => 'test variable',
    ];


  }

}

