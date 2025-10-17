#!/usr/bin/env node
import 'source-map-support/register.js';
import { App } from 'aws-cdk-lib';
import { SscStack } from '../lib/ssc-stack.js';

const app = new App();
new SscStack(app, 'SiteSecureCheckStack');
