<?php

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\NullLogger;
use Sam\ReqresUserService\DTO\UserDto;
use Sam\ReqresUserService\Exception\InvalidUserIdException;
use Sam\ReqresUserService\Exception\UnexpectedApiResponseException;
use Sam\ReqresUserService\Exception\UserCreationException;
use Sam\ReqresUserService\Exception\UserNotFoundException;
use Sam\ReqresUserService\UserService;

class UserServiceTest extends TestCase
{
    private $clientMock;

    private $requestFactoryMock;

    private $userService;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(ClientInterface::class);
        $this->requestFactoryMock = $this->createMock(RequestFactoryInterface::class);
        $streamFactoryMock = $this->createMock(StreamFactoryInterface::class);

        $this->userService = new UserService(
            $this->clientMock,
            $this->requestFactoryMock,
            $streamFactoryMock,
            new NullLogger() // Using NullLogger to ignore logging in tests
        );
    }

    public function testGetUserById()
    {
        $userId = 1;
        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $this->clientMock->method('sendRequest')->willReturn($responseMock);
        $responseMock->method('getStatusCode')->willReturn(200);
        $responseMock->method('getBody')->willReturn($streamMock);
        $streamMock->method('getContents')->willReturn(json_encode([
            'data' => [
                'id' => $userId,
                'email' => 'test@example.com',
                'first_name' => 'Test',
                'last_name' => 'User',
                'avatar' => 'https://example.com/avatar.jpg'
            ]
        ]));

        $userDto = $this->userService->getUserById($userId);

        $this->assertInstanceOf(UserDto::class, $userDto);
        $this->assertEquals($userId, $userDto->jsonSerialize()['id']);
    }

    public function testGetPaginatedUsers()
    {
        $page = 1;
        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $this->clientMock->method('sendRequest')->willReturn($responseMock);
        $responseMock->method('getStatusCode')->willReturn(200);
        $responseMock->method('getBody')->willReturn($streamMock);
        $streamMock->method('getContents')->willReturn(json_encode([
            'page' => $page,
            'data' => [
                ['id' => 1, 'email' => 'user1@example.com', 'first_name' => 'User1', 'last_name' => 'Test1', 'avatar' => 'https://example.com/avatar1.jpg'],
                ['id' => 2, 'email' => 'user2@example.com', 'first_name' => 'User2', 'last_name' => 'Test2', 'avatar' => 'https://example.com/avatar2.jpg']
            ]
        ]));

        $users = $this->userService->getPaginatedUsers($page);

        $this->assertIsArray($users);
        $this->assertCount(2, $users);
        $this->assertInstanceOf(UserDto::class, $users[0]);
        $this->assertEquals(1, $users[0]->jsonSerialize()['id']);
        $this->assertEquals('user1@example.com', $users[0]->jsonSerialize()['email']);
    }

    public function testCreateUser()
    {
        $name = 'New User';
        $job = 'Developer';
        $userId = 101;
        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);
        $requestMock = $this->createMock(RequestInterface::class);

        $this->requestFactoryMock->method('createRequest')->willReturn($requestMock);
        $requestMock->method('withHeader')->willReturnSelf();
        $requestMock->method('withBody')->willReturnSelf();

        $this->clientMock->method('sendRequest')->willReturn($responseMock);
        $responseMock->method('getStatusCode')->willReturn(201);
        $responseMock->method('getBody')->willReturn($streamMock);
        $streamMock->method('getContents')->willReturn(json_encode([
            'name' => $name,
            'job' => $job,
            'id' => $userId,
            'createdAt' => date('Y-m-d H:i:s')
        ]));

        $createdUserId = $this->userService->createUser($name, $job);

        $this->assertIsInt($createdUserId);
        $this->assertEquals($userId, $createdUserId);
    }

    public function testGetUserByIdWithEmptyUserId()
    {
        $this->expectException(InvalidUserIdException::class);

        $this->userService->getUserById(0);
    }

    public function testGetUserByIdWithInvalidUserId()
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $this->clientMock->method('sendRequest')->willReturn($responseMock);
        $responseMock->method('getStatusCode')->willReturn(404);
        $responseMock->method('getBody')->willReturn($streamMock);

        $this->expectException(UserNotFoundException::class);

        $this->userService->getUserById(1);
    }

    public function testGetUserByIdWithInvalidResponseBody()
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $this->clientMock->method('sendRequest')->willReturn($responseMock);
        $responseMock->method('getStatusCode')->willReturn(200);
        $responseMock->method('getBody')->willReturn($streamMock);
        // Simulate an invalid JSON format
        $streamMock->method('getContents')->willReturn('invalid json');

        $this->expectException(UnexpectedApiResponseException::class);

        $this->userService->getUserById(1);
    }

    public function testGetPaginatedUsersHandlesHttpError()
    {
        $responseMock = $this->createMock(ResponseInterface::class);

        $this->clientMock->method('sendRequest')->willReturn($responseMock);
        $responseMock->method('getStatusCode')->willReturn(500); // Simulating an internal server error

        $this->expectException(UnexpectedApiResponseException::class);

        $this->userService->getPaginatedUsers(1);
    }

    public function testGetPaginatedUsersWithEmptyData()
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);

        $this->clientMock->method('sendRequest')->willReturn($responseMock);
        $responseMock->method('getStatusCode')->willReturn(200);
        $responseMock->method('getBody')->willReturn($streamMock);
        $streamMock->method('getContents')->willReturn(json_encode([
            'page' => 1,
            'data' => []
        ]));

        $users = $this->userService->getPaginatedUsers(1);

        $this->assertIsArray($users);
        $this->assertEmpty($users);
    }

    public function testCreateUserHandlesClientException()
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $requestMock = $this->createMock(RequestInterface::class);

        $this->requestFactoryMock->method('createRequest')->willReturn($requestMock);
        $requestMock->method('withHeader')->willReturnSelf();
        $requestMock->method('withBody')->willReturnSelf();

        $this->clientMock->method('sendRequest')->willReturn($responseMock);
        $responseMock->method('getStatusCode')->willReturn(200); // Simulating a successful response

        $this->expectException(UserCreationException::class);

        $this->userService->createUser('New User', 'Developer');
    }

    public function testCreateUserWithUnexpectedApiResponse()
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);
        $requestMock = $this->createMock(RequestInterface::class);

        $this->requestFactoryMock->method('createRequest')->willReturn($requestMock);
        $requestMock->method('withHeader')->willReturnSelf();
        $requestMock->method('withBody')->willReturnSelf();

        $this->clientMock->method('sendRequest')->willReturn($responseMock);
        $responseMock->method('getStatusCode')->willReturn(201);
        $responseMock->method('getBody')->willReturn($streamMock);
        // Simulate a response with unexpected data and missing id
        $streamMock->method('getContents')->willReturn(json_encode([
            'page' => 1,
            'data' => ['user_id' => 1]
        ]));

        $this->expectException(UnexpectedApiResponseException::class);

        $this->userService->createUser('New User', 'Developer');
    }
}
