import { Duration, Stack, StackProps, CfnOutput } from 'aws-cdk-lib';
import { Construct } from 'constructs';
import { Runtime, LayerVersion, Architecture } from 'aws-cdk-lib/aws-lambda';
import { NodejsFunction } from 'aws-cdk-lib/aws-lambda-nodejs';
import * as apigw from 'aws-cdk-lib/aws-apigateway';
import * as iam from 'aws-cdk-lib/aws-iam';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

export class SscStack extends Stack {
  constructor(scope: Construct, id: string, props?: StackProps) {
    super(scope, id, props);

    // Chromium Layer for Puppeteer (using @sparticuz/chromium)
    const chromiumLayer = LayerVersion.fromLayerVersionArn(
      this,
      'ChromiumLayer',
      `arn:aws:lambda:${this.region}:764866452798:layer:chrome-aws-lambda:43`
    );

    // Scanner Lambda - runs headless browser
    const scannerFn = new NodejsFunction(this, 'ScannerFn', {
      runtime: Runtime.NODEJS_20_X,
      architecture: Architecture.X86_64,
      entry: join(__dirname, '../../lambdas/scanner/index.ts'),
      handler: 'handler',
      memorySize: 2048,
      timeout: Duration.seconds(60),
      layers: [chromiumLayer],
      bundling: {
        minify: true,
        externalModules: ['@sparticuz/chromium', 'puppeteer-core']
      },
      environment: {
        NODE_ENV: 'production'
      }
    });

    // Orchestrator Lambda - coordinates scan, calls Bedrock, applies fixes
    const orchestratorFn = new NodejsFunction(this, 'OrchestratorFn', {
      runtime: Runtime.NODEJS_20_X,
      entry: join(__dirname, '../../lambdas/orchestrator/index.ts'),
      handler: 'handler',
      memorySize: 512,
      timeout: Duration.minutes(5),
      bundling: {
        minify: true,
        externalModules: ['@aws-sdk/client-bedrock-runtime']
      },
      environment: {
        SCANNER_FUNCTION_NAME: scannerFn.functionName,
        BEDROCK_MODEL_ID: 'anthropic.claude-3-5-sonnet-20241022-v2:0',
        BEDROCK_REGION: this.region
      }
    });

    // Grant Bedrock access to orchestrator
    orchestratorFn.addToRolePolicy(
      new iam.PolicyStatement({
        actions: ['bedrock:InvokeModel'],
        resources: [
          `arn:aws:bedrock:${this.region}::foundation-model/anthropic.claude-3-5-sonnet-20241022-v2:0`
        ]
      })
    );

    // Allow orchestrator to invoke scanner
    scannerFn.grantInvoke(orchestratorFn);

    // API Gateway
    const api = new apigw.RestApi(this, 'SscApi', {
      restApiName: 'SiteSecureCheck API',
      description: 'Security scanning agent for SiteSecureCheck',
      deployOptions: {
        stageName: 'prod',
        throttlingRateLimit: 10,
        throttlingBurstLimit: 20
      }
    });

    // POST /scan endpoint
    const scan = api.root.addResource('scan');
    scan.addMethod('POST', new apigw.LambdaIntegration(orchestratorFn), {
      apiKeyRequired: false
    });

    // Outputs
    new CfnOutput(this, 'ApiUrl', {
      value: api.url,
      description: 'API Gateway URL'
    });

    new CfnOutput(this, 'ScanEndpoint', {
      value: `${api.url}scan`,
      description: 'Scan endpoint URL (use this for AGENT_SCAN_URL)'
    });
  }
}
