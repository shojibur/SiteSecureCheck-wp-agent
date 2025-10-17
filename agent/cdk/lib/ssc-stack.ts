import { Duration, Stack, StackProps } from 'aws-cdk-lib';
import { Construct } from 'constructs';
import { Runtime } from 'aws-cdk-lib/aws-lambda';
import { NodejsFunction } from 'aws-cdk-lib/aws-lambda-nodejs';
import * as apigw from 'aws-cdk-lib/aws-apigateway';
import { join } from 'path';

export class SscStack extends Stack {
  constructor(scope: Construct, id: string, props?: StackProps) {
    super(scope, id, props);

    const scannerFn = new NodejsFunction(this, 'ScannerFn', {
      runtime: Runtime.NODEJS_20_X,
      entry: join(__dirname, '../../lambdas/scanner/index.ts'),
      handler: 'handler',
      memorySize: 256,
      timeout: Duration.seconds(10)
    });

    const orchestratorFn = new NodejsFunction(this, 'OrchestratorFn', {
      runtime: Runtime.NODEJS_20_X,
      entry: join(__dirname, '../../lambdas/orchestrator/index.ts'),
      handler: 'handler',
      memorySize: 256,
      timeout: Duration.seconds(15)
    });

    const api = new apigw.RestApi(this, 'SscApi', {
      restApiName: 'SiteSecureCheck API'
    });

    const scan = api.root.addResource('scan');
    scan.addMethod('POST', new apigw.LambdaIntegration(orchestratorFn));
  }
}
