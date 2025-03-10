<?php

/**
 * -------------------------------------------------------------------------
 * carbon plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2024 Teclib' and contributors.
 * @copyright Copyright (C) 2024 by the carbon plugin team.
 * @license   MIT https://opensource.org/licenses/mit-license.php
 * @link      https://github.com/pluginsGLPI/carbon
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Carbon\Impact\Embodied\Boavizta;

use CommonDBTM;
use GlpiPlugin\Carbon\DataSource\Boaviztapi;
use GlpiPlugin\Carbon\DataTracking\TrackedFloat;
use GlpiPlugin\Carbon\EmbodiedImpact;
use GlpiPlugin\Carbon\Impact\Embodied\AbstractEmbodiedImpact;
use GlpiPlugin\Carbon\Impact\Embodied\EmbodiedImpactInterface;

abstract class AbstractAsset extends AbstractEmbodiedImpact implements AssetInterface
{
    /** @var string Endpoint to query for the itemtype, to be filled in child class */
    protected string $endpoint       = '';

    /** @var array $hardware hardware description for the request */
    protected array $hardware = [];

    /** @var Boaviztapi instance of the HTTP client */
    protected ?Boaviztapi $client = null;

    // abstract public static function getEngine(CommonDBTM $item): EngineInterface;

    /**
     * Analyze the hardware of the asset to prepare the request to the backend
     * @param CommonDBTM $item asset to analyze
     *
     * @return void
     */
    abstract protected function analyzeHardware(CommonDBTM $item);

    /**
     * Set the REST API client to use for requests
     *
     * @param Boaviztapi $client
     * @return void
     */
    public function setClient(Boaviztapi $client)
    {
        $this->client = $client;
    }

    public function resetImpact(int $items_id): bool
    {
        $embodied_impact = new EmbodiedImpact();
        return $embodied_impact->deleteByCriteria([
            'itemtype' => static::getItemtype(),
            'items_id' => $items_id
        ]);
    }

    protected function query($description): array
    {
        try {
            $response = $this->client->post($this->endpoint, [
                'json' => $description,
            ]);
        } catch (\RuntimeException $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            throw $e;
        }

        return $response;
    }

    /**
     * Read the response to find the impacts provided by Boaviztapi
     *
     * @return array
     */
    protected function parseResponse(array $response): array
    {
        $impacts = [];
        foreach ($response['impacts'] as $type => $impact) {
            if (!in_array($type, $this->getImpactTypes())) {
                trigger_error(sprintf('Unsupported impact type %s in class %s', $type, __CLASS__));
                continue;
            }

            switch ($type) {
                case 'gwp':
                    $impacts[EmbodiedImpactInterface::IMPACT_GWP] = $this->parseGwp($response['impacts']['gwp']);
                    break;
                case 'adp':
                    $impacts[EmbodiedImpactInterface::IMPACT_ADP] = $this->parseAdp($response['impacts']['adp']);
                    break;
                case 'pe':
                    $impacts[EmbodiedImpactInterface::IMPACT_PE] = $this->parsePe($response['impacts']['pe']);
                    break;
            }
        }

        return $impacts;
    }

    protected function parseGwp(array $impact): ?TrackedFloat
    {
        if ($impact['embedded'] === 'not implemented') {
            return null;
        }

        $value = new TrackedFloat(
            $impact['embedded']['value'],
            null,
            TrackedFloat::DATA_QUALITY_ESTIMATED
        );
        if ($impact['unit'] === 'kgCO2eq') {
            $value->setValue($value->getValue() * 1000);
        }

        return $value;
    }

    protected function parseAdp(array $impact): ?TrackedFloat
    {
        if ($impact['embedded'] === 'not implemented') {
            return null;
        }

        $value = new TrackedFloat(
            $impact['embedded']['value'],
            null,
            TrackedFloat::DATA_QUALITY_ESTIMATED
        );
        if ($impact['unit'] === 'kgSbeq') {
            $value->setValue($value->getValue() * 1000);
        }

        return $value;
    }

    protected function parsePe(array $impact): ?TrackedFloat
    {
        if ($impact['embedded'] === 'not implemented') {
            return null;
        }

        $value = new TrackedFloat(
            $impact['embedded']['value'],
            null,
            TrackedFloat::DATA_QUALITY_ESTIMATED
        );
        if ($impact['unit'] === 'MJ') {
            $value->setValue($value->getValue() * (1000 ** 2));
        }

        return $value;
    }
}
