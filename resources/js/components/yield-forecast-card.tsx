import { YieldForecast } from "@/types";
import { Card } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Progress } from "@/components/ui/progress";
import {
    TrendingUp,
    TrendingDown,
    Minus,
    Brain,
    ChevronDown,
    ChevronUp,
    AlertCircle,
    CheckCircle,
    Info,
    AlertTriangle
} from "lucide-react";
import { ChartContainer, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, ResponsiveContainer, BarChart, Bar, Cell } from 'recharts';
import { useState } from "react";

interface YieldForecastCardProps {
    forecast: YieldForecast;
    cropName?: string;
}

const YieldForecastCard = ({ forecast, cropName }: YieldForecastCardProps) => {
    const [isExpanded, setIsExpanded] = useState(true);

    // Convert kg to tons (divide by 1000)
    const predictedYieldTons = forecast.predicted_yield / 1000;
    const expectedYieldTons = forecast.expected_yield ? forecast.expected_yield / 1000 : null;
    const optimisticYieldTons = forecast.optimistic_yield / 1000;
    const pessimisticYieldTons = forecast.pessimistic_yield / 1000;
    const yieldPerAcreTons = forecast.yield_per_acre / 1000;

    // Calculate the variance trend
    const varianceTrend = forecast.variance_from_expected_percent;
    const isPositiveTrend = varianceTrend > 5;
    const isNegativeTrend = varianceTrend < -5;

    // Confidence badge color
    const getConfidenceBadgeColor = (confidence: string) => {
        switch (confidence) {
            case 'high':
                return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
            case 'medium':
                return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
            case 'low':
                return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    // Factor status icon
    const getFactorIcon = (status?: string) => {
        switch (status) {
            case 'good':
                return <CheckCircle className="h-3 w-3 text-green-600 dark:text-green-400" />;
            case 'warning':
                return <AlertTriangle className="h-3 w-3 text-yellow-600 dark:text-yellow-400" />;
            case 'error':
                return <AlertCircle className="h-3 w-3 text-red-600 dark:text-red-400" />;
            default:
                return <Info className="h-3 w-3 text-blue-600 dark:text-blue-400" />;
        }
    };

    // Prepare chart data for historical yields (convert kg to tons)
    const historicalChartData = forecast.historical_yields.map(point => ({
        date: new Date(point.date).toLocaleDateString('en-US', { month: 'short', year: '2-digit' }),
        yield: point.yield / 1000,
        yieldPerAcre: point.yield_per_acre / 1000,
    }));

    // Add current prediction to chart
    const chartDataWithPrediction = [
        ...historicalChartData,
        {
            date: 'Predicted',
            yield: predictedYieldTons,
            yieldPerAcre: yieldPerAcreTons,
            isPrediction: true,
        },
    ];

    // Prepare data for yield comparison bar chart (in tons)
    const comparisonData = [
        {
            name: 'Pessimistic',
            value: pessimisticYieldTons,
            color: '#ef4444', // red-500
        },
        {
            name: 'Predicted',
            value: predictedYieldTons,
            color: '#8b5cf6', // purple-500
        },
        {
            name: 'Optimistic',
            value: optimisticYieldTons,
            color: '#10b981', // green-500
        },
    ];

    // Add expected if available
    if (expectedYieldTons) {
        comparisonData.push({
            name: 'Expected',
            value: expectedYieldTons,
            color: '#6b7280', // gray-500
        });
    }

    return (
        <div className="bg-card border border-border rounded-lg overflow-hidden">
            {/* Header */}
            <div
                className="p-4 cursor-pointer hover:bg-muted/50 transition-colors"
                onClick={() => setIsExpanded(!isExpanded)}
            >
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Brain className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                        <h3 className="text-sm font-semibold">AI Yield Forecast</h3>
                        <Badge className={`text-xs px-1.5 py-0 ${getConfidenceBadgeColor(forecast.confidence)}`}>
                            {forecast.confidence} confidence
                        </Badge>
                        {forecast.model_type === 'ml_regression' && (
                            <Badge className="text-xs px-1.5 py-0 bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                ML Model
                            </Badge>
                        )}
                    </div>
                    {isExpanded ? (
                        <ChevronUp className="h-4 w-4 text-muted-foreground" />
                    ) : (
                        <ChevronDown className="h-4 w-4 text-muted-foreground" />
                    )}
                </div>

                {/* Quick Summary (always visible) */}
                {!isExpanded && (
                    <div className="mt-2 flex items-center gap-4 text-xs">
                        <div>
                            <span className="text-muted-foreground">Predicted: </span>
                            <span className="font-semibold">{predictedYieldTons.toFixed(2)} tons</span>
                        </div>
                        {expectedYieldTons && (
                            <div className="flex items-center gap-1">
                                {isPositiveTrend ? (
                                    <TrendingUp className="h-3 w-3 text-green-600" />
                                ) : isNegativeTrend ? (
                                    <TrendingDown className="h-3 w-3 text-red-600" />
                                ) : (
                                    <Minus className="h-3 w-3 text-gray-600" />
                                )}
                                <span className={isPositiveTrend ? 'text-green-600' : isNegativeTrend ? 'text-red-600' : 'text-gray-600'}>
                                    {varianceTrend > 0 ? '+' : ''}{varianceTrend.toFixed(1)}%
                                </span>
                            </div>
                        )}
                    </div>
                )}
            </div>

            {/* Expanded Content */}
            {isExpanded && (
                <div className="px-4 pb-4 space-y-4">
                    {/* Main Metrics */}
                    <div className="grid grid-cols-2 gap-3">
                        <div className="p-3 bg-muted/30 rounded-lg">
                            <div className="text-xs text-muted-foreground mb-1">AI Predicted Yield</div>
                            <div className="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                {predictedYieldTons.toFixed(2)}
                            </div>
                            <div className="text-xs text-muted-foreground">tons</div>
                            <div className="text-xs mt-1 text-muted-foreground">
                                {yieldPerAcreTons.toFixed(2)} tons/acre
                            </div>
                        </div>

                        {expectedYieldTons ? (
                            <div className="p-3 bg-muted/30 rounded-lg">
                                <div className="text-xs text-muted-foreground mb-1">Expected Yield</div>
                                <div className="text-2xl font-bold">
                                    {expectedYieldTons.toFixed(2)}
                                </div>
                                <div className="text-xs text-muted-foreground">tons</div>
                                <div className="flex items-center gap-1 mt-1">
                                    {isPositiveTrend ? (
                                        <TrendingUp className="h-3 w-3 text-green-600" />
                                    ) : isNegativeTrend ? (
                                        <TrendingDown className="h-3 w-3 text-red-600" />
                                    ) : (
                                        <Minus className="h-3 w-3 text-gray-600" />
                                    )}
                                    <span className={`text-xs font-medium ${isPositiveTrend ? 'text-green-600' : isNegativeTrend ? 'text-red-600' : 'text-gray-600'}`}>
                                        {varianceTrend > 0 ? '+' : ''}{varianceTrend.toFixed(1)}% variance
                                    </span>
                                </div>
                            </div>
                        ) : (
                            <div className="p-3 bg-muted/30 rounded-lg">
                                <div className="text-xs text-muted-foreground mb-1">Model Accuracy</div>
                                <div className="text-2xl font-bold">
                                    {(forecast.confidence_score).toFixed(0)}%
                                </div>
                                <div className="text-xs text-muted-foreground">confidence</div>
                                <div className="text-xs mt-1 text-muted-foreground">
                                    R² = {forecast.r_squared.toFixed(3)}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Growth Progress */}
                    <div className="space-y-2">
                        <div className="flex justify-between text-xs">
                            <span className="text-muted-foreground">Growth Progress</span>
                            <span className="font-medium">{forecast.growth_progress_percent.toFixed(1)}%</span>
                        </div>
                        <Progress value={forecast.growth_progress_percent} className="h-2" />
                        <div className="flex justify-between text-xs text-muted-foreground">
                            <span>Planted</span>
                            <span>{forecast.days_until_harvest} days to harvest</span>
                        </div>
                    </div>

                    {/* Yield Range Comparison Chart */}
                    {comparisonData.length > 0 && (
                        <div className="space-y-2">
                            <div className="text-xs font-semibold">Yield Forecast Range</div>
                            <div className="h-32">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={comparisonData} layout="vertical">
                                        <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                                        <XAxis
                                            type="number"
                                            tick={{ fill: '#6b7280', fontSize: 11 }}
                                            stroke="#9ca3af"
                                        />
                                        <YAxis
                                            dataKey="name"
                                            type="category"
                                            width={80}
                                            tick={{ fill: '#6b7280', fontSize: 11 }}
                                            stroke="#9ca3af"
                                        />
                                        <ChartTooltip
                                            content={({ active, payload }) => {
                                                if (active && payload && payload.length) {
                                                    return (
                                                        <div className="bg-background border border-border rounded-lg p-2 shadow-lg">
                                                            <div className="text-xs font-semibold">{payload[0].payload.name}</div>
                                                            <div className="text-xs text-muted-foreground">
                                                                {Number(payload[0].value).toFixed(2)} tons
                                                            </div>
                                                        </div>
                                                    );
                                                }
                                                return null;
                                            }}
                                        />
                                        <Bar dataKey="value" radius={4}>
                                            {comparisonData.map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={entry.color} />
                                            ))}
                                        </Bar>
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </div>
                    )}

                    {/* Historical Yields Chart */}
                    {forecast.historical_yields.length > 0 && (
                        <div className="space-y-2">
                            <div className="text-xs font-semibold">
                                Historical Yields & Prediction
                                <span className="ml-2 text-muted-foreground font-normal">
                                    ({forecast.sample_size} past harvests)
                                </span>
                            </div>
                            <div className="h-40">
                                <ResponsiveContainer width="100%" height="100%">
                                    <LineChart data={chartDataWithPrediction}>
                                        <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                                        <XAxis
                                            dataKey="date"
                                            tick={{ fill: '#6b7280', fontSize: 10 }}
                                            stroke="#9ca3af"
                                        />
                                        <YAxis
                                            tick={{ fill: '#6b7280', fontSize: 10 }}
                                            stroke="#9ca3af"
                                        />
                                        <ChartTooltip
                                            content={({ active, payload }) => {
                                                if (active && payload && payload.length) {
                                                    return (
                                                        <div className="bg-background border border-border rounded-lg p-2 shadow-lg">
                                                            <div className="text-xs font-semibold">{payload[0].payload.date}</div>
                                                            <div className="text-xs text-muted-foreground">
                                                                Total: {Number(payload[0].value).toFixed(2)} tons
                                                            </div>
                                                            <div className="text-xs text-muted-foreground">
                                                                Per acre: {Number(payload[0].payload.yieldPerAcre).toFixed(2)} tons/acre
                                                            </div>
                                                            {payload[0].payload.isPrediction && (
                                                                <Badge className="text-xs mt-1 bg-purple-100 text-purple-800">
                                                                    AI Prediction
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    );
                                                }
                                                return null;
                                            }}
                                        />
                                        <Line
                                            type="monotone"
                                            dataKey="yield"
                                            stroke="#8b5cf6"
                                            strokeWidth={2}
                                            dot={{ fill: '#8b5cf6', r: 4 }}
                                            activeDot={{ r: 6 }}
                                        />
                                    </LineChart>
                                </ResponsiveContainer>
                            </div>
                        </div>
                    )}

                    {/* Environmental Factors */}
                    <div className="space-y-2">
                        <div className="text-xs font-semibold">Contributing Factors</div>
                        <div className="space-y-1.5">
                            {forecast.environmental_factors.map((factor, index) => (
                                <div
                                    key={index}
                                    className="flex items-start gap-2 p-2 bg-muted/20 rounded text-xs"
                                >
                                    <div className="mt-0.5">{getFactorIcon(factor.status)}</div>
                                    <div className="flex-1">
                                        <div className="font-medium">{factor.factor}</div>
                                        <div className="text-muted-foreground text-xs">{factor.impact}</div>
                                    </div>
                                    <div className="text-xs text-muted-foreground font-medium">
                                        {factor.weight}%
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Model Info */}
                    <div className="p-2 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded text-xs">
                        <div className="flex items-center gap-1 text-purple-800 dark:text-purple-300 font-medium">
                            <Brain className="h-3 w-3" />
                            <span>
                                {forecast.model_type === 'ml_regression'
                                    ? `Multiple Linear Regression Model (R² = ${forecast.r_squared.toFixed(3)})`
                                    : 'Basic Estimate (Insufficient historical data)'}
                            </span>
                        </div>
                        <div className="text-purple-700 dark:text-purple-400 mt-1">
                            {forecast.model_type === 'ml_regression'
                                ? `This forecast uses machine learning trained on ${forecast.sample_size} historical harvests, considering soil moisture patterns, planting density, and growing conditions.`
                                : 'More accurate predictions will be available after collecting additional harvest data.'}
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default YieldForecastCard;
