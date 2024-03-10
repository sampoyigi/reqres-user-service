<?php

namespace Sam\ReqresUserService;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Sam\ReqresUserService\DTO\UserDto;
use Sam\ReqresUserService\Exception\ApiException;
use Sam\ReqresUserService\Exception\InvalidUserIdException;
use Sam\ReqresUserService\Exception\UnexpectedApiResponseException;
use Sam\ReqresUserService\Exception\UserCreationException;
use Sam\ReqresUserService\Exception\UserNotFoundException;

class UserService
{
    private const BASE_URI = 'https://reqres.in/api/users';

    private ClientInterface $client;

    private RequestFactoryInterface $requestFactory;

    private StreamFactoryInterface $streamFactory;

    private LoggerInterface $logger;

    public function __construct(
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->logger = $logger;
    }

    public function getUserById(int $id): ?UserDto
    {
        try {
            if ($id <= 0) {
                throw new InvalidUserIdException("The user ID must be positive.");
            }

            $request = $this->requestFactory->createRequest('GET', self::BASE_URI."/$id");
            $response = $this->client->sendRequest($request);

            if ($response->getStatusCode() === 404) {
                throw new UserNotFoundException("User not found with ID $id");
            }

            if ($response->getStatusCode() !== 200) {
                throw new UnexpectedApiResponseException("Unexpected API response status: ".$response->getStatusCode());
            }

            if (!$body = $response->getBody()) {
                throw new ApiException("Failed to create user, empty API response body");
            }

            $contents = json_decode($body->getContents(), true);

            return empty($contents['data'])
                ? throw new UnexpectedApiResponseException("Unexpected API response: ".json_encode($contents))
                : UserDto::fromArray($contents['data']);
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('HTTP client error fetching user by ID: ' . $e->getMessage(), ['exception' => $e]);
            return null;
        }
    }

    public function getPaginatedUsers(int $page): array
    {
        try {
            $request = $this->requestFactory->createRequest('GET', self::BASE_URI."?page=$page");

            $response = $this->client->sendRequest($request);

            if ($response->getStatusCode() !== 200) {
                throw new UnexpectedApiResponseException("Unexpected API response status: ".$response->getStatusCode());
            }

            if (!$body = $response->getBody()) {
                throw new ApiException("Failed to create user, empty API response body");
            }

            $contents = json_decode($body->getContents(), true);

            return array_map(fn($user) => UserDto::fromArray($user), $contents['data']);
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('HTTP client error fetching paginated users: ' . $e->getMessage(), ['exception' => $e]);
            return [];
        }
    }

    public function createUser(string $name, string $job): int
    {
        try {
            $body = $this->streamFactory->createStream(json_encode(['name' => $name, 'job' => $job]));

            $request = $this->requestFactory
                ->createRequest('POST', self::BASE_URI)
                ->withBody($body)
                ->withHeader('Content-Type', 'application/json');

            $response = $this->client->sendRequest($request);

            if ($response->getStatusCode() !== 201) {
                throw new UserCreationException("Failed to create user, API response status: ".$response->getStatusCode());
            }

            if (!$body = $response->getBody()) {
                throw new ApiException("Failed to create user, empty API response body");
            }

            $contents = json_decode($body->getContents(), true);

            return empty($contents['id'])
                ? throw new UnexpectedApiResponseException("Unexpected API response: ".json_encode($contents))
                : $contents['id'];
        } catch (ClientExceptionInterface $e) {
            $this->logger->error('HTTP client error creating user: ' . $e->getMessage(), ['exception' => $e]);
            return 0;
        }
    }
}