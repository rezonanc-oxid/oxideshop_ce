<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace OxidEsales\EshopCommunity\Internal\Service;

use OxidEsales\Eshop\Application\Model\Review;
use OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\EshopCommunity\Application\Model\Rating;
use OxidEsales\EshopCommunity\Internal\Exception\ArticleReviewTypeException;
use OxidEsales\EshopCommunity\Internal\Exception\ArticleReviewPermissionException;

/**
 * Class UserService
 * @package OxidEsales\EshopCommunity\Internal\Service
 */
class UserService implements UserServiceInterface
{
    /**
     * @var User
     */
    private $user;

    /**
     * @var DatabaseInterface
     */
    private $database;

    /**
     * @var int
     */
    private $shopId;

    /**
     * UserService constructor.
     *
     * @param User              $user
     * @param DatabaseInterface $database
     * @param int               $shopId
     */
    public function __construct(
        User $user,
        DatabaseInterface $database,
        $shopId
    ) {
        $this->user = $user;
        $this->database =  $database;
        $this->shopId = $shopId;
    }

    /**
     * @param int $count
     * @param int $offset
     *
     * @return mixed
     */
    public function getArticleReviews($count, $offset)
    {
        $review = oxNew(Review::class);

        return $review->getProductReviewsByUserId(
            $this->user->getId(),
            $offset,
            $count
        );
    }

    /**
     * Deletes User Article Review.
     *
     * @param Review $review
     */
    public function deleteArticleReview(Review $review)
    {
        $this->validateUserPermissionToDeleteReview($review);
        $this->validateArticleReview($review);

        $this->database->startTransaction();
        try {
            $this->deleteArticleReviewRating($review);
            $review->delete();

            $this->database->commitTransaction();
        } catch (\Exception $exception) {
            $this->database->rollbackTransaction();
            throw new $exception;
        }
    }

    /**
     * @param Review $review
     * @throws ArticleReviewPermissionException
     */
    private function validateUserPermissionToDeleteReview(Review $review)
    {
        if (!$this->isCurrentUserReview($review)) {
            throw new ArticleReviewPermissionException('Review doesn\'t belong to current user.');
        }
    }

    /**
     * @param Review $review
     *
     * @return bool
     */
    private function isCurrentUserReview(Review $review)
    {
        $reviewUser = $review->getUser();

        return $this->user->getId() === $reviewUser->getId();
    }

    /**
     * @param Review $review
     * @throws ArticleReviewTypeException
     */
    private function validateArticleReview(Review $review)
    {
        if (!$this->isArticleReview($review)) {
            throw new ArticleReviewTypeException('The review is not an article review.');
        }
    }

    /**
     * @param Review $review
     *
     * @return bool
     */
    private function isArticleReview(Review $review)
    {
        return 'oxarticle' === $review->getObjectType();
    }

    /**
     * @param Review $review
     */
    private function deleteArticleReviewRating(Review $review)
    {
        if ($this->hasArticleReviewRating($review)) {
            $rating = $this->getArticleReviewRating($review);
            $rating->delete();
        }
    }

    /**
     * @param Review $review
     * @return bool
     */
    private function hasArticleReviewRating(Review $review)
    {
        return  $review->oxreviews__oxrating->value > 0;
    }

    /**
     * @param Review $review
     * @return Rating
     */
    private function getArticleReviewRating(Review $review)
    {
        $ratingType = 'oxarticle';
        $query = '
            SELECT 
                OXID 
            FROM 
                oxratings 
            WHERE 
              OXOBJECTID = ?
              AND OXUSERID = ?
              AND OXSHOPID = ?
              AND OXTYPE = ?
        ';

        $ratingId = $this->database->getOne($query, [
            $review->getObjectId(),
            $this->user->getId(),
            $this->shopId,
            $ratingType
        ]);

        $rating = oxNew(Rating::class);
        $rating->load($ratingId);

        return $rating;
    }
}
