<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Application\Controller;

use OxidEsales\Eshop\Application\Model\Review;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\EshopCommunity\Internal\Service\UserService;

/**
 * Class AccountReviewController
 *
 * @package OxidEsales\EshopCommunity\Application\Controller
 */
class AccountReviewController extends AccountController
{
    protected $itemsPerPage = 10;

    protected $_sThisTemplate = 'page/account/articlereviews.tpl';

    /**
     * Redirect to My Account, if validation does not pass.
     */
    public function init()
    {
        if (!$this->isUserAllowedToManageOwnArticleReviews() || !$this->getUser()) {
            $this->redirectToAccountDashboard();
        }

        parent::init();
    }

    /**
     * Returns Article Review List
     *
     * @return \OxidEsales\Eshop\Core\Model\ListModel|null
     */
    public function getArticleReviewList()
    {
        $currentPage    = $this->getActPage();
        $itemsPerPage   = $this->getItemsPerPage();
        $offset         = $currentPage * $itemsPerPage;

        $userService = $this->getUserService();

        return $userService->getArticleReviews($itemsPerPage, $offset);
    }

    /**
     * Delete an article review and rating, which belongs to the active user.
     *
     * @return string
     */
    public function deleteArticleReviewAndRating()
    {
        if ($this->getSession()->checkSessionChallenge()) {
            $review = $this->getReviewFromRequest();

            $userService = $this->getUserService();
            $userService->deleteArticleReview($review);
        }

        return $this->getArticleReviewListUrlPath();
    }

    /**
     * Returns Bread Crumb - you are here page1/page2/page3...
     *
     * @return array
     */
    public function getBreadCrumb()
    {
        return [
            [
                'title' => $this->getTranslatedString('MY_ACCOUNT'),
                'link'  => $this->getMyAccountPageUrl(),
            ],
            [
                'title' => $this->getTranslatedString('MY_PRODUCT_REVIEWS'),
                'link'  => $this->getLink(),
            ],
        ];
    }

    /**
     * Generates the pagination.
     *
     * @return \stdClass
     */
    public function getPageNavigation()
    {
        $this->_iCntPages       = $this->getPagesCount();
        $this->_oPageNavigation = $this->generatePageNavigation();

        return $this->_oPageNavigation;
    }

    /**
     * Return how many items will be displayed per page.
     *
     * @return int
     */
    public function getItemsPerPage()
    {
        return $this->itemsPerPage;
    }

    /**
     * @return string
     */
    private function getArticleReviewListUrlPath()
    {
        $lastPage = $this->getPagesCount();
        $currentPage = $this->getActPage();

        if ($currentPage >= $lastPage) {
            $currentPage = $lastPage - 1;
        }

        return $currentPage > 0 ? 'account_reviewlist?pgNr=' . $currentPage : 'account_reviewlist';
    }

    /**
     * Redirect to My Account dashboard
     */
    private function redirectToAccountDashboard()
    {
        Registry::getUtils()->redirect(
            $this->getMyAccountPageUrl(),
            true,
            302
        );
        exit(0);
    }

    /**
     * Returns pages count.
     *
     * @return int
     */
    private function getPagesCount()
    {
        return ceil($this->getArticleReviewItemsCnt() / $this->getItemsPerPage());
    }

    /**
     * Returns My Account page url.
     *
     * @return string
     */
    private function getMyAccountPageUrl()
    {
        $selfLink = $this->getViewConfig()->getSelfLink();

        return Registry::getSeoEncoder()->getStaticUrl($selfLink . 'cl=account');
    }

    /**
     * Returns translated string.
     *
     * @param string $string
     *
     * @return string
     */
    private function getTranslatedString($string)
    {
        $languageId = Registry::getLang()->getBaseLanguage();

        return Registry::getLang()->translateString(
            $string,
            $languageId,
            false
        );
    }

    /**
     * Retrieve the Review from the request
     *
     * @return Review
     */
    private function getReviewFromRequest()
    {
        $request = oxNew(Request::class);
        $reviewId = $request->getRequestEscapedParameter('reviewId');

        $review = oxNew(Review::class);
        $review->load($reviewId);

        return $review;
    }

    /**
     * @return UserService
     */
    private function getUserService()
    {
        return new UserService(
            $this->getUser(),
            DatabaseProvider::getDb(),
            Registry::getConfig()->getShopId()
        );
    }
}
