<?php

use LBHurtado\XDocumentLaravel\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');
pest()->in('Unit', 'Architecture');
