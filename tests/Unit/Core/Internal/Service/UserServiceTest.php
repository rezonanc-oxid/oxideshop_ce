<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */
namespace OxidEsales\EshopCommunity\Tests\Unit\Core\Internal\Service;

use OxidEsales\Eshop\Application\Model\Review;
use OxidEsales\Eshop\Application\Model\User;
use OxidEsales\Eshop\Core\Database\Adapter\DatabaseInterface;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\EshopCommunity\Internal\Service\UserService;

class UserServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException OxidEsales\EshopCommunity\Internal\Exception\ArticleReviewPermissionException
     */
    public function testUserTriesToDeleteForeignReview()
    {
        $database = $this->getDatabaseMock();

        $user = $this->getMockWithDisabledConstructor(User::class);
        $user
            ->method('getId')
            ->willReturn(1);

        $ownerOfReview = $this->getMockWithDisabledConstructor(User::class);
        $ownerOfReview
            ->method('getId')
            ->willReturn(2);

        $review = $this->getMockWithDisabledConstructor(Review::class);
        $review
            ->method('getUser')
            ->willReturn($ownerOfReview);

        $userService = new UserService(
            $user,
            $database,
            1
        );

        $userService->deleteArticleReview($review);
    }

    /**
     * @expectedException OxidEsales\EshopCommunity\Internal\Exception\ArticleReviewTypeException
     */
    public function testUserTriesToDeleteNotArticleReview()
    {
        $database = $this->getDatabaseMock();

        $user = $this->getMockWithDisabledConstructor(User::class);
        $user
            ->method('getId')
            ->willReturn(1);

        $review = $this->getMockWithDisabledConstructor(Review::class);
        $review
            ->method('getUser')
            ->willReturn($user);

        $review
            ->method('getObjectType')
            ->willReturn('notAnArticle');

        $userService = new UserService(
            $user,
            $database,
            1
        );

        $userService->deleteArticleReview($review);
    }

    public function testUserDeleteArticleReviewWithoutRating()
    {
        $database = $this->getDatabaseMock();

        $user = $this->getMockWithDisabledConstructor(User::class);
        $user
            ->method('getId')
            ->willReturn(1);

        $review = $this->getMockWithDisabledConstructor(Review::class);
        $review
            ->method('getUser')
            ->willReturn($user);

        $review
            ->method('getObjectType')
            ->willReturn('oxarticle');

        $review
            ->method('__get')
            ->willReturn(new Field(0));

        $review
            ->expects($this->once())
            ->method('delete');

        $userService = new UserService(
            $user,
            $database,
            1
        );

        $userService->deleteArticleReview($review);
    }

    private function getDatabaseMock()
    {
        return $this
            ->getMockBuilder(DatabaseInterface::class)
            ->getMock();
    }

    private function getMockWithDisabledConstructor($className)
    {
        return $this
            ->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
    }
}