        <?php if( !empty($posts) ): ?>
        <ul>
           <?php foreach ($posts as $post): ?>
            <li>
                <h2><?php echo $post['title']; ?></h2><br />
                <div class="post_status">Статус: <?php echo $post['name']; ?></div>
                       <?php echo "Опубликовано: ";
                             echo $post['create_time']; ?> <br />
                     <?php echo nl2br(htmlentities($post['body'])); ?>
            </li>
            <br /><hr />
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p>
            Публикаций пока нет.
        </p>
        <?php endif; ?>

