<?php

use Tests\FeatureTestCase;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Unit');
pest()->extend(FeatureTestCase::class)->in('Feature');
