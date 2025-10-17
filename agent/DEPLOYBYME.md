(Use `node --trace-deprecation ...` to show where the warning was created)
 ⏳  Bootstrapping environment aws://497870328661/ap-northeast-1...
Trusted accounts for deployment: (none)
Trusted accounts for lookup: (none)
Using default execution policy of 'arn:aws:iam::aws:policy/AdministratorAccess'. Pass '--cloudformation-execution-policies' to customize.
CDKToolkit: creating CloudFormation changeset...
 ✅  Environment aws://497870328661/ap-northeast-1 bootstrapped.




wehn i run npx cdk deploy 



➜  agent git:(main) ✗ npx cdk deploy
(node:93661) ExperimentalWarning: `--experimental-loader` may be removed in the future; instead use `register()`:
--import 'data:text/javascript,import { register } from "node:module"; import { pathToFileURL } from "node:url"; register("ts-node/esm", pathToFileURL("./"));'
(Use `node --trace-warnings ...` to show where the warning was created)
(node:93661) [DEP0180] DeprecationWarning: fs.Stats constructor is deprecated.
(Use `node --trace-deprecation ...` to show where the warning was created)
Bundling asset SiteSecureCheckStack/ScannerFn/Code/Stage...

  cdk.out/bundling-temp-35057475ba2f42c6d656b1c4c57a7c702c3cb4f5f009f8cdc804cee2fbab48b6-building/index.js  3.5kb

⚡ Done in 4ms
Bundling asset SiteSecureCheckStack/OrchestratorFn/Code/Stage...

  cdk.out/bundling-temp-e73034508b57ea0b63dfd3ff53b14858aa686e56174ef4db037f913c073d160e-building/index.js  491.8kb

⚡ Done in 60ms

✨  Synthesis time: 3.51s

current credentials could not be used to assume 'arn:aws:iam::497870328661:role/cdk-hnb659fds-deploy-role-497870328661-ap-northeast-1', but are for the right account. Proceeding anyway.
SiteSecureCheckStack: start: Building ScannerFn/Code
SiteSecureCheckStack: success: Built ScannerFn/Code
SiteSecureCheckStack: start: Building OrchestratorFn/Code
SiteSecureCheckStack: success: Built OrchestratorFn/Code
SiteSecureCheckStack: start: Building SiteSecureCheckStack Template
SiteSecureCheckStack: success: Built SiteSecureCheckStack Template
SiteSecureCheckStack: start: Publishing SiteSecureCheckStack Template (current_account-current_region-7f7e27c8)
SiteSecureCheckStack: start: Publishing OrchestratorFn/Code (current_account-current_region-6c2101c3)
SiteSecureCheckStack: start: Publishing ScannerFn/Code (current_account-current_region-8e536245)
current credentials could not be used to assume 'arn:aws:iam::497870328661:role/cdk-hnb659fds-file-publishing-role-497870328661-ap-northeast-1', but are for the right account. Proceeding anyway.
current credentials could not be used to assume 'arn:aws:iam::497870328661:role/cdk-hnb659fds-file-publishing-role-497870328661-ap-northeast-1', but are for the right account. Proceeding anyway.
current credentials could not be used to assume 'arn:aws:iam::497870328661:role/cdk-hnb659fds-file-publishing-role-497870328661-ap-northeast-1', but are for the right account. Proceeding anyway.
SiteSecureCheckStack: success: Published ScannerFn/Code (current_account-current_region-8e536245)
SiteSecureCheckStack: success: Published SiteSecureCheckStack Template (current_account-current_region-7f7e27c8)
SiteSecureCheckStack: success: Published OrchestratorFn/Code (current_account-current_region-6c2101c3)
current credentials could not be used to assume 'arn:aws:iam::497870328661:role/cdk-hnb659fds-lookup-role-497870328661-ap-northeast-1', but are for the right account. Proceeding anyway.
Lookup role arn:aws:iam::497870328661:role/cdk-hnb659fds-lookup-role-497870328661-ap-northeast-1 was not assumed. Proceeding with default credentials.
Stack SiteSecureCheckStack
IAM Statement Changes
┌───┬────────────────────────────────────────────────────────────────────────────────────────────┬────────┬───────────────────────┬───────────────────────────────────┬────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│   │ Resource                                                                                   │ Effect │ Action                │ Principal                         │ Condition                                                                                                                                          │
├───┼────────────────────────────────────────────────────────────────────────────────────────────┼────────┼───────────────────────┼───────────────────────────────────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ + │ ${OrchestratorFn.Arn}                                                                      │ Allow  │ lambda:InvokeFunction │ Service:apigateway.amazonaws.com  │ "ArnLike": {                                                                                                                                       │
│   │                                                                                            │        │                       │                                   │   "AWS:SourceArn": "arn:${AWS::Partition}:execute-api:${AWS::Region}:${AWS::AccountId}:${SscApi157E28EF}/${SscApi/DeploymentStage.prod}/POST/scan" │
│   │                                                                                            │        │                       │                                   │ }                                                                                                                                                  │
│ + │ ${OrchestratorFn.Arn}                                                                      │ Allow  │ lambda:InvokeFunction │ Service:apigateway.amazonaws.com  │ "ArnLike": {                                                                                                                                       │
│   │                                                                                            │        │                       │                                   │   "AWS:SourceArn": "arn:${AWS::Partition}:execute-api:${AWS::Region}:${AWS::AccountId}:${SscApi157E28EF}/test-invoke-stage/POST/scan"              │
│   │                                                                                            │        │                       │                                   │ }                                                                                                                                                  │
├───┼────────────────────────────────────────────────────────────────────────────────────────────┼────────┼───────────────────────┼───────────────────────────────────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ + │ ${OrchestratorFn/ServiceRole.Arn}                                                          │ Allow  │ sts:AssumeRole        │ Service:lambda.amazonaws.com      │                                                                                                                                                    │
├───┼────────────────────────────────────────────────────────────────────────────────────────────┼────────┼───────────────────────┼───────────────────────────────────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ + │ ${ScannerFn.Arn}                                                                           │ Allow  │ lambda:InvokeFunction │ AWS:${OrchestratorFn/ServiceRole} │                                                                                                                                                    │
│   │ ${ScannerFn.Arn}:*                                                                         │        │                       │                                   │                                                                                                                                                    │
├───┼────────────────────────────────────────────────────────────────────────────────────────────┼────────┼───────────────────────┼───────────────────────────────────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ + │ ${ScannerFn/ServiceRole.Arn}                                                               │ Allow  │ sts:AssumeRole        │ Service:lambda.amazonaws.com      │                                                                                                                                                    │
├───┼────────────────────────────────────────────────────────────────────────────────────────────┼────────┼───────────────────────┼───────────────────────────────────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ + │ ${SscApi/CloudWatchRole.Arn}                                                               │ Allow  │ sts:AssumeRole        │ Service:apigateway.amazonaws.com  │                                                                                                                                                    │
├───┼────────────────────────────────────────────────────────────────────────────────────────────┼────────┼───────────────────────┼───────────────────────────────────┼────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ + │ arn:aws:bedrock:${AWS::Region}::foundation-model/anthropic.claude-3-5-sonnet-20241022-v2:0 │ Allow  │ bedrock:InvokeModel   │ AWS:${OrchestratorFn/ServiceRole} │                                                                                                                                                    │
└───┴────────────────────────────────────────────────────────────────────────────────────────────┴────────┴───────────────────────┴───────────────────────────────────┴────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
IAM Policy Changes
┌───┬───────────────────────────────┬─────────────────────────────────────────────────────────────────────────────────────────┐
│   │ Resource                      │ Managed Policy ARN                                                                      │
├───┼───────────────────────────────┼─────────────────────────────────────────────────────────────────────────────────────────┤
│ + │ ${OrchestratorFn/ServiceRole} │ arn:${AWS::Partition}:iam::aws:policy/service-role/AWSLambdaBasicExecutionRole          │
├───┼───────────────────────────────┼─────────────────────────────────────────────────────────────────────────────────────────┤
│ + │ ${ScannerFn/ServiceRole}      │ arn:${AWS::Partition}:iam::aws:policy/service-role/AWSLambdaBasicExecutionRole          │
├───┼───────────────────────────────┼─────────────────────────────────────────────────────────────────────────────────────────┤
│ + │ ${SscApi/CloudWatchRole}      │ arn:${AWS::Partition}:iam::aws:policy/service-role/AmazonAPIGatewayPushToCloudWatchLogs │
└───┴───────────────────────────────┴─────────────────────────────────────────────────────────────────────────────────────────┘
(NOTE: There may be security-related changes not in this list. See https://github.com/aws/aws-cdk/issues/1299)

"--require-approval" is enabled and stack includes security-sensitive updates: 'Do you wish to deploy these changes' (y/n) y
SiteSecureCheckStack: deploying... [1/1]
current credentials could not be used to assume 'arn:aws:iam::497870328661:role/cdk-hnb659fds-deploy-role-497870328661-ap-northeast-1', but are for the right account. Proceeding anyway.
SiteSecureCheckStack: creating CloudFormation changeset...

 ✅  SiteSecureCheckStack

✨  Deployment time: 74.54s

Outputs:
SiteSecureCheckStack.ApiUrl = https://rkxqqebs9a.execute-api.ap-northeast-1.amazonaws.com/prod/
SiteSecureCheckStack.ScanEndpoint = https://rkxqqebs9a.execute-api.ap-northeast-1.amazonaws.com/prod/scan
SiteSecureCheckStack.SscApiEndpoint8F3AD8F8 = https://rkxqqebs9a.execute-api.ap-northeast-1.amazonaws.com/prod/
Stack ARN:
arn:aws:cloudformation:ap-northeast-1:497870328661:stack/SiteSecureCheckStack/3f7b1f40-abb1-11f0-b258-0673e705bc81

✨  Total time: 78.05s
