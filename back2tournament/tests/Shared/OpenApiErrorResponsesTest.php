<?php

declare(strict_types=1);

namespace App\Tests\Shared;

use Nelmio\ApiDocBundle\Render\RenderOpenApi;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class OpenApiErrorResponsesTest extends KernelTestCase
{
    private const VERBS = ['get', 'post', 'put', 'patch', 'delete'];

    public function test_every_failure_documents_the_body_it_answers(): void
    {
        $document = $this->document();

        $bodiless = [];
        foreach ($document['paths'] as $path => $operations) {
            foreach (array_intersect_key($operations, array_flip(self::VERBS)) as $verb => $operation) {
                foreach ($operation['responses'] as $status => $response) {
                    if ((int) $status < 400) {
                        continue;
                    }
                    if (isset($response['$ref'])) {
                        $response = $document['components']['responses'][basename($response['$ref'])];
                    }
                    if (!isset($response['content']['application/json']['schema'])) {
                        $bodiless[] = \sprintf('%s %s %s', strtoupper($verb), $path, $status);
                    }
                }
            }
        }

        $this->assertSame([], $bodiless);
    }

    public function test_a_refusal_names_its_reason(): void
    {
        $schemas = $this->document()['components']['schemas'];

        $this->assertSame(['error'], $schemas['Error']['required']);
        $this->assertSame(['code', 'message'], $schemas['AuthenticationError']['required']);
    }

    /** @return array<string, mixed> */
    private function document(): array
    {
        self::bootKernel();

        /** @var RenderOpenApi $renderer */
        $renderer = static::getContainer()->get('nelmio_api_doc.render_docs');

        return json_decode($renderer->render(RenderOpenApi::JSON, 'default'), true, 512, JSON_THROW_ON_ERROR);
    }
}
