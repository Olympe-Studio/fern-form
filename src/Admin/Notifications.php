<?

declare(strict_types=1);

namespace Fern\Form\Admin;

use Fern\Form\FernFormPlugin;

final class Notifications {
  /** @var string */
  public const READ_STATUS_META_KEY = '_fern_form_read_status';

  /** @var string */
  private string $postType;

  private function __construct(string $postType) {
    $this->postType = $postType;
  }

  /**
   * Boot up the notification system
   *
   * @param string $postType The post type to monitor
   * @return void
   */
  public static function boot(string $postType): void {
    $instance = new self($postType);

    add_action('admin_head', [$instance, 'addNotificationStyles']);
    // Remove wp_count_posts filter as we'll handle count differently
    add_action('_admin_menu', [$instance, 'updateMenuWithNotification']);
    add_action('admin_init', [$instance, 'markSubmissionsAsRead']);
  }

  /**
   * Update the admin menu with notification count
   *
   * @return void
   */
  public function updateMenuWithNotification(): void {
    // Prevent multiple executions
    static $done = false;
    if ($done) {
      return;
    }
    $done = true;

    global $menu;

    $unreadCount = count($this->getUnreadPostIds());
    if ($unreadCount === 0) {
      return;
    }

    foreach ($menu as $key => $item) {
      if ($item[2] === 'edit.php?post_type=' . $this->postType) {
        $count = $unreadCount > 99 ? '99+' : (string)$unreadCount;
        $menu[$key][0] .= " <span class=\"update-plugins count-{$unreadCount}\"><span class=\"plugin-count\">{$count}</span></span>";
        break;
      }
    }
  }

  /**
   * Add notification styles to admin header
   *
   * @return void
   */
  public function addNotificationStyles(): void {
    $pt = esc_attr($this->postType);

    echo <<<HTML
    <style>
      #menu-posts-{$pt} .wp-menu-name {
        position: relative;
      }
      #menu-posts-{$pt} .update-plugins {
        display: inline-block;
        vertical-align: top;
        box-sizing: border-box;
        margin: 1px 0 -1px 2px;
        padding: 0 5px;
        min-width: 18px;
        height: 18px;
        border-radius: 9px;
        background-color: #ca4a1f;
        color: #fff;
        font-size: 11px;
        line-height: 1.6;
        text-align: center;
        z-index: 26;
      }
    </style>
    HTML;

    $this->addRowStyling();
  }

  /**
   * Add styling for unread rows in the submissions list
   *
   * @return void
   */
  private function addRowStyling(): void {
    if (!$this->isSubmissionListPage()) {
      return;
    }

    $unreadPosts = wp_json_encode($this->getUnreadPostIds());

    echo <<<HTML
            <style>
                .fern-form-unread td:not(.column-cb) {
                    background-color: #fff8e5;
                }
                .fern-form-unread td:not(.column-cb) .row-title {
                    color: #135e96;
                    font-weight: 600;
                }
            </style>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const unreadPosts = {$unreadPosts};
                    unreadPosts.forEach(postId => {
                        const row = document.getElementById('post-' + postId);
                        if (row) {
                            row.classList.add('fern-form-unread');
                        }
                    });
                });
            </script>
        HTML;
  }

  /**
   * Mark a submission as read when viewing it
   *
   * @return void
   */
  public function markSubmissionsAsRead(): void {
    if (!isset($_GET['action'], $_GET['post']) || $_GET['action'] !== 'edit') {
      return;
    }

    $postId = (int)$_GET['post'];
    $postType = get_post_type($postId);

    if ($postType !== FernFormPlugin::POST_TYPE_NAME) {
      return;
    }

    update_post_meta($postId, self::READ_STATUS_META_KEY, 'read');
  }

  /**
   * Get all unread submission IDs
   *
   * @return array<int>
   */
  private function getUnreadPostIds(): array {
    $query = new \WP_Query([
      'post_type' => $this->postType,
      'post_status' => 'publish',
      'fields' => 'ids',
      'posts_per_page' => -1,
      'meta_query' => [
        'relation' => 'OR',
        [
          'key' => self::READ_STATUS_META_KEY,
          'value' => 'unread',
          'compare' => '='
        ],
        [
          'key' => self::READ_STATUS_META_KEY,
          'compare' => 'NOT EXISTS'
        ]
      ]
    ]);

    return array_map('intval', $query->posts);
  }

  /**
   * Check if current page is the submissions list
   *
   * @return bool
   */
  private function isSubmissionListPage(): bool {
    global $pagenow, $post_type;
    return $pagenow === 'edit.php' && $post_type === $this->postType;
  }
}
