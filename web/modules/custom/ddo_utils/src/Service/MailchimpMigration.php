<?php

namespace Drupal\ddo_utils\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\node\NodeInterface;
use Psr\Log\LoggerInterface;

/**
 * Class helper to ease migration from Mailchimp as a newsletter provider.
 */
class MailchimpMigration {

  public function __construct(
    protected LoggerInterface $logger,
    protected FileSystemInterface $fileSystem,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Attach PDF export files of newsletters to corresponding nodes.
   *
   * @param string $dir
   *   Base directory where CSV and PDF files are located.
   */
  public function migrate(string $dir) : void {
    // Convert $base_dir to an absolute path instead of a stream wrapper. Useful
    // to deal with functions like glob() and scandir(), which simply map system
    // calls without dealing with stream wrappers.
    $base_dir = $this->fileSystem->realpath($dir);
    $pdf_dir = $base_dir . '/pdf';

    // Index PDF file names by slug.
    $index = $this->scan($pdf_dir);
    if (empty($index)) {
      $this->logger->error('No PDF Mailchimp export files found in %dir', ['%dir' => $pdf_dir]);
      return;
    }

    // Open CSV list of campaigns provided by Mailchimp.
    $csv = $base_dir . '/campaigns.csv';

    if ($handle = fopen($csv, 'r')) {
      while (($line = fgetcsv($handle, NULL, ',')) !== FALSE) {
        // Actually the title is all the data we need.
        $title = $line[0] ?? '';
        $old_id = $line[22] ?? '';
        if (empty($title)) {
          continue;
        }

        // Transliterate file name just as Mailchimp does and find campaign ID.
        $slug = $this->transliterate($title);
        if (!isset($index[$slug])) {
          continue;
        }
        [$id, $filename] = $index[$slug];

        // Look for corresponding node.
        $node = $this->lookup($id, $old_id);
        if (!$node) {
          continue;
        }
        // Attach file to this node.
        if ($node->field_pdf_export->isEmpty()) {
          $this->attachFile($node, $dir . '/pdf/' . $filename);
        }
      }
      fclose($handle);
    }
    else {
      $this->logger->error('Could not open %csv', ['%csv' => $csv]);
    }
  }

  /**
   * Transliterate file names as Mailchimp does.
   *
   * Lower case, all non pure alphanumeric characters replaced by hyphens.
   *
   * @param string $string
   *   String to be transliterated.
   *
   * @return string
   *   Transliterated string.
   */
  protected function transliterate(string $string) : string {
    // Lowercase.
    $result = strtolower($string);
    // Non pure alphanumeric characters replaced by hyphens.
    $result = preg_replace('/[^a-z0-9]+/', '-', $result);

    return $result;
  }

  /**
   * Scan PDF files and index them by slug.
   *
   * @param string $pdf_dir
   *   Directory which contains PDF files.
   *
   * @return array
   *   Mailchimp ID / file name pairs, indexed by slug.
   */
  protected function scan(string $pdf_dir) : array {
    $files = scandir($pdf_dir);
    $index = [];

    if (!$files) {
      return $index;
    }

    foreach ($files as $file) {
      if (!preg_match('/(\d+)_([a-z0-9-]+)\.pdf/', $file, $matches)) {
        continue;
      }
      [$filename, $id, $slug] = $matches;
      $index[$slug] = [$id, $filename];
    }

    return $index;
  }

  /**
   * Look for newsletter node corresponding to a given Mailchimp pseudo ID.
   *
   * @param string $id
   *   Mailchimp numeric pseudo identifier.
   * @param string $old_id
   *   Mailchimp unique ID, used in a deprecated URL format.
   *
   * @return \Drupal\node\NodeInterface|null
   *   Node related to this identifier.
   */
  protected function lookup(string $id, string $old_id) : ?NodeInterface {
    $node_storage = $this->entityTypeManager->getStorage('node');

    $query = $node_storage->getQuery()
      ->accessCheck(FALSE);

    $uri_condition = $query->orConditionGroup();
    $uri_condition->condition('field_mailchimp_url.uri', '%' . $id . '%', 'LIKE');
    $uri_condition->condition('field_mailchimp_url.uri', '%id=' . $old_id . '%', 'LIKE');

    $nids = $query->condition('type', 'newsletter')
      ->condition($uri_condition)
      ->execute();

    if (empty($nids)) {
      return NULL;
    }

    $nid = current($nids);

    return $node_storage->load($nid);
  }

  /**
   * Attach a PDF file to a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Newsletter node which has to be updated.
   * @param string $filename
   *   Complete path of file.
   */
  protected function attachFile(NodeInterface $node, string $filename) : void {
    // Create a file entity.
    $file = $this->entityTypeManager
      ->getStorage('file')
      ->create([
        'filename' => basename($filename),
        'uri' => $filename,
        'status' => 1,
        'uid' => 1,
      ]);
    $file->save();

    // Attach it to the node.
    $node->set('field_pdf_export', $file->id());
    $node->save();
  }

}
