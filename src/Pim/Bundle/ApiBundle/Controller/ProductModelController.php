<?php

declare(strict_types=1);

namespace Pim\Bundle\ApiBundle\Controller;

use Akeneo\Component\StorageUtils\Exception\PropertyException;
use Akeneo\Component\StorageUtils\Factory\SimpleFactoryInterface;
use Akeneo\Component\StorageUtils\Saver\SaverInterface;
use Akeneo\Component\StorageUtils\Updater\ObjectUpdaterInterface;
use Pim\Bundle\ApiBundle\Documentation;
use Pim\Component\Api\Exception\DocumentedHttpException;
use Pim\Component\Api\Exception\ViolationHttpException;
use Pim\Component\Catalog\Exception\InvalidArgumentException;
use Pim\Component\Catalog\Model\ProductModelInterface;
use Pim\Component\Catalog\Query\Filter\Operators;
use Pim\Component\Catalog\Query\ProductQueryBuilderFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @author    Willy MESNAGE <willy.mesnage@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductModelController
{
    /** @var ProductQueryBuilderFactoryInterface */
    protected $pqbFactory;

    /** @var NormalizerInterface */
    protected $normalizer;

    /** @var ObjectUpdaterInterface */
    protected $updater;

    /** @var SaverInterface */
    protected $saver;

    /** @var UrlGeneratorInterface */
    protected $router;

    /** @var ValidatorInterface */
    protected $productModelValidator;

    /** @var SimpleFactoryInterface */
    protected $productModelFactory;

    /**
     * @param ProductQueryBuilderFactoryInterface $pqbFactory
     * @param NormalizerInterface                 $normalizer
     * @param ObjectUpdaterInterface              $updater
     * @param SimpleFactoryInterface              $productModelFactory
     * @param SaverInterface                      $saver
     * @param ValidatorInterface                  $productModelValidator
     * @param UrlGeneratorInterface               $router
     */
    public function __construct(
        ProductQueryBuilderFactoryInterface $pqbFactory,
        NormalizerInterface $normalizer,
        ObjectUpdaterInterface $updater,
        SimpleFactoryInterface $productModelFactory,
        SaverInterface $saver,
        ValidatorInterface $productModelValidator,
        UrlGeneratorInterface $router
    ) {
        $this->pqbFactory = $pqbFactory;
        $this->normalizer = $normalizer;
        $this->updater = $updater;
        $this->saver = $saver;
        $this->router = $router;
        $this->productModelValidator = $productModelValidator;
        $this->productModelFactory = $productModelFactory;
    }

    /**
     * @param string $code
     *
     * @throws NotFoundHttpException
     *
     * @return JsonResponse
     */
    public function getAction($code): JsonResponse
    {
        $pqb = $this->pqbFactory->create();
        $pqb->addFilter('identifier', Operators::EQUALS, $code);
        $productModels = $pqb->execute();

        if (0 === $productModels->count()) {
            throw new NotFoundHttpException(sprintf('Product model "%s" does not exist.', $code));
        }

        $productModelApi = $this->normalizer->normalize($productModels->current(), 'standard');

        return new JsonResponse($productModelApi);
    }

    /**
     * @param Request $request
     *
     * @throws BadRequestHttpException
     *
     * @return Response
     */
    public function createAction(Request $request): Response
    {
        $data = $this->getDecodedContent($request->getContent());

        $productModel = $this->productModelFactory->create();

        $this->updateProductModel($productModel, $data, 'post_product-models');
        $this->validateProductModel($productModel);
        $this->saver->save($productModel);

        $response = $this->getResponse($productModel, Response::HTTP_CREATED);

        return $response;
    }

    /**
     * Get the JSON decoded content. If the content is not a valid JSON, it throws an error 400.
     *
     * @param string $content content of a request to decode
     *
     * @throws BadRequestHttpException
     *
     * @return array
     */
    protected function getDecodedContent($content): array
    {
        $decodedContent = json_decode($content, true);

        if (null === $decodedContent) {
            throw new BadRequestHttpException('Invalid json message received');
        }

        return $decodedContent;
    }

    /**
     * Update a product. It throws an error 422 if a problem occurred during the update.
     *
     * @param ProductModelInterface $productModel category to update
     * @param array                 $data         data of the request already decoded
     * @param string                $anchor
     *
     * @throws DocumentedHttpException
     */
    protected function updateProductModel(ProductModelInterface $productModel, array $data, string $anchor): void
    {
        try {
            $this->updater->update($productModel, $data);
        } catch (PropertyException $exception) {
            throw new DocumentedHttpException(
                Documentation::URL . $anchor,
                sprintf('%s Check the standard format documentation.', $exception->getMessage()),
                $exception
            );
        } catch (InvalidArgumentException $exception) {
            throw new AccessDeniedHttpException($exception->getMessage(), $exception);
        }
    }

    /**
     * Get a response with a location header to the created or updated resource.
     *
     * @param ProductModelInterface $productModel
     * @param int              $status
     *
     * @return Response
     */
    protected function getResponse(ProductModelInterface $productModel, int $status): Response
    {
        $response = new Response(null, $status);
        $route = $this->router->generate(
            'pim_api_product_get',
            ['code' => $productModel->getIdentifier()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $response->headers->set('Location', $route);

        return $response;
    }

    /**
     * Validate a product. It throws an error 422 with every violated constraints if
     * the validation failed.
     *
     * @param ProductModelInterface $product
     *
     * @throws ViolationHttpException
     */
    protected function validateProductModel(ProductModelInterface $product): void
    {
        $violations = $this->productModelValidator->validate($product, null, ['Default', 'api']);
        if (0 !== $violations->count()) {
            throw new ViolationHttpException($violations);
        }
    }
}
