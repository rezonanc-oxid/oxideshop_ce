<?php

namespace OxidEsales\EshopCommunity\Internal\Service;

use OxidEsales\Eshop\Application\Model\Review;

/**
 * Interface UserServiceInterface
 * @package OxidEsales\EshopCommunity\Internal\Service
 */
interface UserServiceInterface
{
    /**
     * @param int $count
     * @param int $offset
     *
     * @return mixed
     */
    public function getArticleReviews($count, $offset);

    /**
     * @param Review $review
     */
    public function deleteArticleReview(Review $review);
}
