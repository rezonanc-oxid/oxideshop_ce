<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Application\Controller;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Core\DatabaseProvider;

/**
 * Class AccountReviewController
 *
 * @package OxidEsales\EshopCommunity\Application\Controller
 */
class AccountReviewController extends \OxidEsales\Eshop\Application\Controller\AccountController
{

    protected $itemsPerPage = 10;

    /**
     * Redirect to My Account, if feature is not enabled
     */
    public function init()
    {
        if (!$this->isUserAllowedToManageHisProductReviews()) {
            $this->redirectToAccountDashboard();
        }

        parent::init();
    }

    /**
     * Show the Reviews list only, if the feature has been enabled in eShop Admin
     * -> Master Settings -> Core Settings -> Settings -> Account settings -> "Allow users to manage their product reviews"
     *
     * @return string
     */
    public function render()
    {
        $this->_sThisTemplate = 'page/account/productreviews.tpl';
        return parent::render();
    }

    /**
     * Generates the pagination
     *
     * @return \stdClass
     */
    public function getPageNavigation()
    {
        $this->_iCntPages = ceil($this->getProductReviewItemsCnt() / $this->getItemsPerPage());
        $this->_oPageNavigation = $this->generatePageNavigation();

        return $this->_oPageNavigation;
    }

    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function getBreadCrumb()
    {
        $languageId = Registry::getLang()->getBaseLanguage();
        $selfLink = $this->getViewConfig()->getSelfLink();

        /**
         * Parent level breadcrumb.
         * Note: parent::getBreadCrumb() cannot be used here, as a different string will be rendered.
         */
        $breadCrumbPaths[] = [
            'title' => Registry::getLang()->translateString('MY_ACCOUNT', $languageId, false),
            'link'  => Registry::getSeoEncoder()->getStaticUrl($selfLink . 'cl=account')
        ];

        /** Own level breadcrumb */
        $breadCrumbPaths[] = [
            'title' => Registry::getLang()->translateString('MY_PRODUCT_REVIEWS', $languageId, false),
            'link'  => $this->getLink()
        ];

        return $breadCrumbPaths;
    }

    /**
     * Return how many items will be displayed per page
     *
     * @return int
     */
    public function getItemsPerPage()
    {
        return $this->itemsPerPage;
    }

    /**
     * Get a list of a range of product reviews for the active user.
     * The range to retrieve is determined by the offset and rowCount parameters
     * which behave like in the MySQL LIMIT clause
     *
     * @return \OxidEsales\Eshop\Core\Model\ListModel|null
     */
    public function getProductReviewList()
    {
        $productReviewList = null;

        if ($user = $this->getUser()) {
            $currentPage = $this->getActPage();
            $offset = $currentPage * $this->getItemsPerPage();
            $rowCount = $this->getItemsPerPage();

            $userId = $user->getId();

            $review = oxNew(\OxidEsales\Eshop\Application\Model\Review::class);
            $productReviewList = $review->getProductReviewsByUserId($userId, $offset, $rowCount);
        }

        return $productReviewList;
    }

    /**
     * Delete a product review and rating, which belongs to the active user.
     * Keep in mind, that this method may return only false or void. Any other return value will cause malfunction in
     * higher layers
     *
     * @return bool False, if the review cannot be deleted, because the validation failed
     *
     * @throws \Exception
     */
    public function deleteProductReviewAndRating()
    {
        $user = $this->getUser();
        
        if ($user && $this->getSession()->checkSessionChallenge()) {
            $db = DatabaseProvider::getDb();
            $db->startTransaction();

            try {
                $ratingDeleted = true;

                $articleId = $this->getArticleIdFromRequest();
                if (!$articleId ||
                    !$this->deleteProductRating($userId, $articleId)
                ) {
                    $ratingDeleted = false;
                }

                /** The review id must be given to be able to delete a single review */
                $reviewId = $this->getReviewIdFromRequest();
                if (!$ratingDeleted ||
                    !$reviewId ||
                    !$this->deleteProductReview($userId, $reviewId)
                ) {
                    $reviewDeleted = false;
                } else {
                    $reviewDeleted = true;
                }

                if ($ratingDeleted && $reviewDeleted) {
                    $db->commitTransaction();
                } else {
                    $db->rollbackTransaction();
                    Registry::getUtilsView()->addErrorToDisplay('ERROR_PRODUCT_REVIEW_AND_RATING_NOT_DELETED');
                }
            } catch (\Exception $exception) {
                $db->rollbackTransaction();

                throw $exception;
            }
        } else {
            Registry::getUtilsView()->addErrorToDisplay('ERROR_PRODUCT_REVIEW_AND_RATING_NOT_DELETED');

            return false;
        }

        $lastPageNr = ceil($this->getProductReviewItemsCnt() / $this->getItemsPerPage());
        $pgNr = $this->getActPage();
        if ($pgNr >= $lastPageNr) {
            $pgNr = $lastPageNr - 1;
        }
        if ($pgNr > 0) {
            return 'account_reviewlist?pgNr=' . $pgNr;
        } else {
            return 'account_reviewlist';
        }
    }

    /**
     * Delete a given review for a given user
     *
     * @param string $userId    Id of the user the rating belongs to
     * @param string $articleId Id of the rating to delete
     */
    protected function deleteProductRating($userId, $articleId)
    {
        $shopId = Registry::getConfig()->getShopId();
        $rating = oxNew(\OxidEsales\Eshop\Application\Model\Rating::class);

        $ratingId = $rating->getProductRatingByUserId($articleId, $userId, $shopId);
        if ($ratingId) {
            $rating->delete($ratingId);
        }
    }

    /**
     * Delete a given review for a given user
     *
     * @param string $userId   Id of the user the review belongs to
     * @param string $reviewId Id of the review to delete
     *
     * @return bool True, if the review has been deleted, False if the validation failed
     *
     */
    protected function deleteProductReview($userId, $reviewId)
    {
        /** The review must exist */
        $review = oxNew(\OxidEsales\Eshop\Application\Model\Review::class);
        if (!$review->load($reviewId)) {
            return false;
        }

        /** It must be a product review */
        if ('oxarticle' !== $review->getObjectType()) {
            return false;
        }

        /** It must belong to the active user */
        $reviewUserId = $review->getUser()->getId();
        if ($reviewUserId != $userId) {
            return false;
        };

        $review->delete($reviewId);

        return true;
    }

    /**
     * Retrieve the article ID from the request
     *
     * @return string
     */
    protected function getArticleIdFromRequest()
    {
        $request = oxNew(\OxidEsales\Eshop\Core\Request::class);
        $articleId = $request->getRequestEscapedParameter('aId', '');

        return $articleId;
    }

    /**
     * Retrieve the review ID from the request
     *
     * @return string
     */
    protected function getReviewIdFromRequest()
    {
        $request = oxNew(\OxidEsales\Eshop\Core\Request::class);
        $reviewId = $request->getRequestEscapedParameter('reviewId', '');

        return $reviewId;
    }

    /**
     * Redirect to My Account dashboard
     */
    protected function redirectToAccountDashboard()
    {
        $myAccountLink = $this->getViewConfig()->getSelfLink() . 'cl=account';
        $myAccountUrl = Registry::getUtilsUrl()->processUrl($myAccountLink);

        Registry::getUtils()->redirect(
            $myAccountUrl,
            true,
            302
        );
        exit(0);
    }
}
