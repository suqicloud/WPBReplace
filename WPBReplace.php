<?php
/*
Plugin Name: WPBReplace Plugin
Plugin URI: https://www.jingxialai.com/4251.html
Description: 批量替换文章标题、内容、摘要、标签的插件
Version: 1.3
Author: Summer
License: GPL License
*/

// 添加菜单
function batch_replace_menu() {
    add_menu_page(
        '批量替换',
        '批量替换',
        'manage_options',
        'batch-replace',
        'batch_replace_page'
    );
    // 设置入口
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'batch_replace_settings_link');
}
add_action('admin_menu', 'batch_replace_menu');

// 设置链接回调函数
function batch_replace_settings_link($links) {
    $settings_link = '<a href="admin.php?page=batch-replace">设置</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// 插件页面
function batch_replace_page() {
    global $wpdb;
    ?>
<style>
.wrap {
    max-width: 95%; 
    padding: 10px;
    background-color: #fff;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    border-radius: 5px;
}

.wrap h2 {
    color: #333;
    font-size: 24px;
}

.wrap form {
    margin-bottom: 20px;
    padding: 10px;
    background-color: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 5px;
}

.wrap form input[type="text"] {
    width: 300px;
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.wrap form input[type="submit"] {
    margin-left: 10px;
    padding: 5px 10px;
    background-color: #0073aa;
    color: #fff;
    border: none;
    border-radius: 3px;
    cursor: pointer;
}

.wrap form input[type="submit"]:hover {
    background-color: #009fd5;
}

.wrap ul {
    list-style-type: disc;
    padding-left: 20px;
}

.wrap ul li a {
    color: #0073aa;
}

.wrap ul li a:hover {
    color: #009fd5;
}

#cache-tooltip {
    font-size: 12px;
    margin-top: 5px;
}
form:hover #cache-tooltip {
    display: block;
}
.message-container {
    margin-top: 20px; 
}
.message {
    padding: 10px;
    margin-top: 10px;
    border: 1px solid #ddd;
    background-color: #f9f9f9;
    border-radius: 5px;
}

.warning-button {
        margin-left: 10px;
        padding: 5px 10px;
        background-color: #dc3232;
        color: #FF3300;
        border: none;
        border-radius: 3px;
        cursor: not-allowed;
    }
.wrap form input[type="text"] {
    width: 200px; 
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 3px;
}
</style>
<div class="wrap">
<?php
    // 直接替换
    if (isset($_POST['replace_text_direct_submit'])) {
        $old_text = sanitize_text_field($_POST['old_text']);
        $new_text = sanitize_text_field($_POST['new_text']);
        $replace_location = sanitize_text_field($_POST['replace_location']);

        if (empty($old_text) || empty($new_text)) {
            echo '<div class="message">请填写原始内容和替换内容。</div>';
        } else {
            switch ($replace_location) {
                case 'title':
                    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->posts} SET post_title = REPLACE(post_title, %s, %s)", $old_text, $new_text));
                    echo '<div class="message">标题替换成功！</div>';
                    break;
                case 'content':
                    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s)", $old_text, $new_text));
                    echo '<div class="message">内容替换成功！</div>';
                    break;
                case 'excerpt':
                    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->posts} SET post_excerpt = REPLACE(post_excerpt, %s, %s)", $old_text, $new_text));
                    echo '<div class="message">摘要替换成功！</div>';
                    break;
                case 'tags':
                    // 搜索标签
                    $args = array(
                        'posts_per_page' => -1,
                        'post_type' => 'post',
                        'post_status' => 'publish',
                        'suppress_filters' => true,
                    );
                    $posts = get_posts($args);

                    foreach ($posts as $post) {
                        $tags = wp_get_post_tags($post->ID);
                        if ($tags) {
                            foreach ($tags as $tag) {
                                $tag_name = $tag->name;
                                $new_tag_name = str_replace($old_text, $new_text, $tag_name);
                                wp_update_term($tag->term_id, 'post_tag', array('name' => $new_tag_name));
                            }
                        }
                    }

                    echo '<div class="message">标签替换成功！</div>';
                    break;
                default:
                    echo '<div class="message">请选择替换位置。</div>';
            }
        }
    }

    // 批量替换链接
if (isset($_POST['replace_links_submit'])) {
    $old_link = sanitize_text_field($_POST['old_link']);
    $new_link = sanitize_text_field($_POST['new_link']);

    if (empty($old_link) || empty($new_link)) {
        echo '<div class="message">请填写原始链接和新链接。</div>';
    } else {
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s)", $old_link, $new_link));
        echo '<div class="message">链接替换成功！</div>';
    }
}
    ?>

<!-- 批量替换链接 -->
<h3>批量替换文章中的链接(比如www.abc.com替换成www.ddd.com)</h3>
<form method="post" action="" class="replace-links-form">
    <label for="old_link">原始链接：</label>
    <input type="text" name="old_link" id="old_link" required>

    <label for="new_link">新链接：</label>
    <input type="text" name="new_link" id="new_link" required>

    <input type="submit" name="replace_links_submit" value="批量替换链接">
</form>
<!-- 批量替换链接功能结束 -->

<h3>直接替换指定的内容(标题、内容、摘要、标签)</h3>
        <form method="post" action="" class="replace-form">
        <label for="old_text">原始内容：</label>
        <input type="text" name="old_text" id="old_text" required>

        <label for="new_text">替换内容：</label>
        <input type="text" name="new_text" id="new_text" required>

        <label for="replace_location">选择替换位置：</label>
        <select name="replace_location" id="replace_location">
            <option value="title">标题</option>
            <option value="content">内容</option>
            <option value="excerpt">摘要</option>
            <option value="tags">标签</option>
        </select>

        <input type="submit" name="replace_text_direct_submit" value="直接替换">
    </form>
<!-- 直接替换结束-->
 
<!-- 清除对象缓存 -->
<form method="post" action="" style="margin-top: 10px;">
    <input type="submit" name="clear_translation_cache" value="清除对象缓存" aria-describedby="cache-tooltip">
    <input type="button" class="warning-button" value="和数据库有关的操作，替换前一定要备份数据库！请提前备份数据库！请提前备份数据库！" disabled style="color: #FF3300;">
    <div id="cache-tooltip" style="font-size: 12px; margin-top: 5px;">1、如果网站开启了对象缓存，替换之后可能需要清除对象缓存。2、比如搜索123、就会把标题或者文章里面带有123的文章都搜索出来，再根据需求替换。<br>3、替换标签内容会慢点，特别是文章多的情况下。4、其实链接替换和文章中的内容替换是一样的，单加一个更方便使用。<br>5、如果想用sql命令进数据库操作，可以查看：<a href="https://www.jingxialai.com/4251.html" target="_blank">此插件介绍</a>里面有提到相关教程
        <br>插件QQ群：<a target="_blank" href="https://qm.qq.com/cgi-bin/qm/qr?k=dgfThTp7nW4_hoRc1wjaGWEKlNmemlqB&jump_from=webapi&authKey=kwUfvush+fV1G/4Mvr5cva6EnWnQPave2J61QzmfTmUEk+OdGg6c9H1tPaHQYjLJ"><img border="0" src="//pub.idqqimg.com/wpa/images/group.png" alt="Wordpress运营技术瞎折腾" title="Wordpress运营技术瞎折腾"></a></div>
</form> 
<!-- 清除对象缓存结束 -->

<!-- 搜索替换 -->
        <h3>先搜索标题或者文章里面的指定内容</h3>
        <form method="post" action="">
            <label for="search_text">搜索指定内容(标题和文章里面的内容)：</label>
            <input type="text" name="search_text" id="search_text" required>
            <input type="submit" name="search" value="搜索">
        </form>

        <?php
        // 处理搜索
        if (isset($_POST['search'])) {
            $search_text = sanitize_text_field($_POST['search_text']);

    $args = array(
        's' => $search_text,
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 20,
    );

            $query = new WP_Query($args);

            if ($query->have_posts()) {
                ?>
                <h3>搜索结果：</h3>

                <!-- 搜索结果到这里替换 -->
                <form method="post" action="">
                    <label>搜索内容：</label>
                    <input type="text" name="search_text" id="search_text" value="<?php echo esc_attr($search_text); ?>" readonly>
                    <label for="replace_text">替换为：</label>
                    <input type="text" name="replace_text" id="replace_text" value="" required>
                    <input type="submit" name="replace_title" value="替换标题内容">
                    <input type="submit" name="replace_content" value="替换文章内容">
                </form>

                <ul>
                    <?php 
                    $post_count = 0;
                    while ($query->have_posts() && $post_count < 20) : $query->the_post(); 
                        if (stripos(get_the_title(), $search_text) !== false || stripos(strip_tags(get_the_content()), $search_text) !== false) {
                            ?>
                            <li><a href="<?php the_permalink(); ?>" target="_blank"><?php the_title(); ?></a></li>
                            <?php
                            $post_count++;
                        }
                    endwhile; ?>
                </ul>

                <?php
                if ($query->found_posts > 20) {
                    echo '<p>相关文章超过20篇，剩下的省略不显示。</p>';
                }

                wp_reset_postdata();
            } else {
                echo '<div class="message">未找到匹配的文章。</div>';
            }
        }
        ?>

        <?php
        // 处理替换标题
        if (isset($_POST['replace_title'])) {
            $search_text = sanitize_text_field($_POST['search_text']);
            $replace_text = sanitize_text_field($_POST['replace_text']);

            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->posts} SET post_title = REPLACE(post_title, %s, %s)", $search_text, $replace_text));

            echo '<div class="message">标题替换成功！</div>';
        }

        // 处理替换内容
        if (isset($_POST['replace_content'])) {
            $search_text = sanitize_text_field($_POST['search_text']);
            $replace_text = sanitize_text_field($_POST['replace_text']);

            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s)", $search_text, $replace_text));

            echo '<div class="message">内容替换成功！</div>';
        }
        //搜索替换结束

        // 清除对象缓存
        if (isset($_POST['clear_translation_cache'])) {
            wp_cache_flush();
            echo '<div class="message">对象缓存已清除！</div>';
        }
        ?>

    </div>
    <?php
}
?>
