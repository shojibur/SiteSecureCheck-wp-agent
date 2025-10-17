export type PlanAction =
  | { type: 'add_header'; key: string; value: string }
  | { type: 'set_csp'; mode: 'report-only' | 'enforce' };

export function defaultPlan() {
  const actions: PlanAction[] = [
    { type: 'add_header', key: 'Strict-Transport-Security', value: 'max-age=31536000; includeSubDomains; preload' },
    { type: 'add_header', key: 'Referrer-Policy', value: 'strict-origin-when-cross-origin' },
    { type: 'set_csp', mode: 'report-only' }
  ];
  return { actions };
}

export default defaultPlan;
