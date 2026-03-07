export interface DonutSegment {
  label: string;
  value: number;
  color: string;
}

export interface MetricDonutData {
  title: string;
  totalLabel: string;
  totalValue: number | string;
  segments: DonutSegment[];
  height?: number;
}

export interface LinePoint {
  x: string | number;
  y: number;
}

export interface LineSeries {
  name: string;
  color: string;
  points: LinePoint[];
}

export interface MetricLineChartData {
  title: string;
  yLabel: string;
  xLabels: string[];
  series: LineSeries[];
  gridSteps?: number;
}

export interface MetricTrendPoint {
  x: string | number;
  y: number;
}

export type MetricTrendType = 'increase' | 'decrease' | 'neutral';

export interface MetricKpi {
  title: string;
  value: string | number;
  deltaText: string;
  trend: MetricTrendPoint[];
  trendType: MetricTrendType;
  accentColor: string;
  icon?: string;
}
