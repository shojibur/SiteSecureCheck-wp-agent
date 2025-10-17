import type { APIGatewayProxyHandler } from 'aws-lambda';

export const handler: APIGatewayProxyHandler = async () => {
  const body = {
    tls: { https: true, hsts: false },
    headers: {},
    cookies: [],
    third_parties: [],
    csp_present: false
  };
  return { statusCode: 200, body: JSON.stringify(body) };
};
