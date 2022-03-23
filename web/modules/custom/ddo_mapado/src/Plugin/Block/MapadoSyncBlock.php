<?php

namespace Drupal\ddo_mapado\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Bloc affichant l'état activé/désactivé de la synchronisation Mapddo.
 *
 * @Block(
 *  id = "mapado_sync_block",
 *  admin_label = @Translation("Mapado synchronization block"),
 *  category = @Translation("Custom")
 * )
 */
class MapadoSyncBlock extends BlockBase {

  public function build() {
    $block_title = "Mapado synchronization";
    $config = \Drupal::config('ddo_mapado.settings');
    if ($config->get('enable_sync')) {
      $sync_enabled = true;
      $mapado_class = "enabled";
    }
    else {
      $sync_enabled = false;
      $mapado_class = "disabled";
    }

    return [
      '#theme' => 'ddo_mapado_block',
      '#block_title' => $block_title,
      '#sync_enabled' => $sync_enabled,
      '#mapado_class' => $mapado_class,
      '#cache' => ['max-age' => 0],
      '#attached' => ['library' => ['ddo_mapado/mapado.sync.status']],
    ];
  }
}
