<?php
function ensure_news_votes_table(mysqli $conn)
{
    $sql = "CREATE TABLE IF NOT EXISTS news_votes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        news_id INT NOT NULL,
        vote_type ENUM('up','down') NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_news_votes_user
            FOREIGN KEY (user_id) REFERENCES users(id)
            ON DELETE CASCADE,
        CONSTRAINT fk_news_votes_article
            FOREIGN KEY (news_id) REFERENCES news_posts(id)
            ON DELETE CASCADE,
        UNIQUE KEY unique_user_news_vote (user_id, news_id),
        KEY idx_news_votes_news_type (news_id, vote_type)
    )";

    return $conn->query($sql) === true;
}

function vote_select_columns($userId = 0)
{
    $userId = (int) $userId;
    $userVoteSql = "''";
    if ($userId > 0) {
        $userVoteSql = "(SELECT nv.vote_type FROM news_votes nv WHERE nv.news_id = n.id AND nv.user_id = $userId LIMIT 1)";
    }

    return ",
           (SELECT COUNT(*) FROM news_votes nv WHERE nv.news_id = n.id AND nv.vote_type = 'up') AS up_votes,
           (SELECT COUNT(*) FROM news_votes nv WHERE nv.news_id = n.id AND nv.vote_type = 'down') AS down_votes,
           $userVoteSql AS user_vote";
}

function vote_score(array $item)
{
    return (int) ($item['up_votes'] ?? 0) - (int) ($item['down_votes'] ?? 0);
}

function vote_label($count, $singular)
{
    $count = (int) $count;
    return $count . ' ' . $singular . ($count === 1 ? '' : 's');
}
?>
