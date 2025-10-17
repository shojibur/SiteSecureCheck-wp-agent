import type { APIGatewayProxyHandler } from 'aws-lambda';
import defaultPlan from './plan.js';

type Req = {
  site_id?: string;
  domain?: string;
  wp_api_base?: string;
  wp_api_key?: string;
  region_mode?: string;
  auto_fix?: boolean;
  callback?: string;
  callback_token?: string;
  apply?: boolean;
};

export const handler: APIGatewayProxyHandler = async (event) => {
  const body: Req = event.body ? JSON.parse(event.body) : {};
  const { site_id = '', apply = false } = body;

  const resp = {
    site_id,
    score: 80,
    issues: [] as any[],
    plan: defaultPlan(),
    applied: !!apply,
    logs: ['stub']
  };

  return { statusCode: 200, body: JSON.stringify(resp) };
};
