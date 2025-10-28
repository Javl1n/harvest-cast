import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { 
    Droplets, 
    Thermometer, 
    CloudIcon, 
    RefreshCw, 
    AlertTriangle, 
    CheckCircle, 
    Sun, 
    CloudRain, 
    Wind, 
    Snowflake, 
    Sprout, 
    TreePine, 
    Flower, 
    Apple, 
    Wheat, 
    Leaf, 
    Bug,
    Heart,
    Clock
} from "lucide-react";
import { useState } from "react";

interface CropCareRecommendation {
    action: string;
    description: string;
    icon: string;
    priority: 'high' | 'medium' | 'low';
    category: string;
}

interface CurrentConditions {
    soil_moisture: number;
    temperature?: number;
    weather_condition?: string;
    humidity?: number;
    reading_date: string;
    weather_date?: string;
}

interface CropCareRecommendationsProps {
    recommendations: CropCareRecommendation[];
    currentConditions: CurrentConditions | null;
    cropName?: string;
    daysSincePlanting?: number;
}

const getIconComponent = (iconName: string) => {
    const icons: Record<string, React.ComponentType<any>> = {
        'droplets': Droplets,
        'alert-triangle': AlertTriangle,
        'check-circle': CheckCircle,
        'thermometer': Thermometer,
        'snowflake': Snowflake,
        'cloud-rain': CloudRain,
        'sun': Sun,
        'wind': Wind,
        'sprout': Sprout,
        'tree-pine': TreePine,
        'flower': Flower,
        'apple': Apple,
        'wheat': Wheat,
        'leaf': Leaf,
        'bug': Bug,
    };
    
    return icons[iconName] || CheckCircle;
};

const getPriorityColor = (priority: string) => {
    switch (priority) {
        case 'high':
            return 'bg-red-500/20 text-red-400 border-red-500/30';
        case 'medium':
            return 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30';
        case 'low':
            return 'bg-green-500/20 text-green-400 border-green-500/30';
        default:
            return 'bg-gray-500/20 text-gray-400 border-gray-500/30';
    }
};

const getCategoryColor = (category: string) => {
    switch (category) {
        case 'watering':
            return 'text-blue-600';
        case 'weather':
            return 'text-purple-600';
        case 'growth_stage':
            return 'text-green-600';
        case 'nutrition':
            return 'text-orange-600';
        case 'pest_control':
            return 'text-red-600';
        case 'drainage':
            return 'text-indigo-600';
        default:
            return 'text-gray-600';
    }
};

const CropCareRecommendations = ({ 
    recommendations, 
    currentConditions, 
    cropName,
    daysSincePlanting 
}: CropCareRecommendationsProps) => {
    const [loading, setLoading] = useState(false);

    const refreshRecommendations = async () => {
        setLoading(true);
        // Refresh the page to get updated recommendations
        setTimeout(() => {
            window.location.reload();
        }, 500);
    };

    if (!currentConditions || recommendations.length === 0) {
        return (
            <div className="bg-card border border-border rounded-lg p-4">
                <div className="flex items-center gap-2 mb-3">
                    <Heart className="h-4 w-4" />
                    <h3 className="text-sm font-semibold">Crop Care</h3>
                </div>
                <div className="p-4 bg-muted/10 rounded border border-border/50">
                    <p className="text-sm text-muted-foreground">
                        {!currentConditions 
                            ? "No sensor readings available for care recommendations."
                            : "No active plantings or care recommendations available."
                        }
                    </p>
                </div>
            </div>
        );
    }

    return (
        <div className="bg-card border border-border rounded-lg p-4 space-y-3">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <Heart className="h-4 w-4" />
                    <h3 className="text-sm font-semibold">Crop Care Recommendations</h3>
                    {cropName && (
                        <Badge variant="outline" className="text-xs">
                            {cropName}
                        </Badge>
                    )}
                </div>
                <Button 
                    variant="ghost" 
                    size="sm" 
                    onClick={refreshRecommendations}
                    disabled={loading}
                    className="h-6 w-6 p-0"
                >
                    <RefreshCw className={`h-3 w-3 ${loading ? 'animate-spin' : ''}`} />
                </Button>
            </div>
            
            {/* Current Conditions Summary */}
            <div className="grid grid-cols-4 gap-2 p-2 bg-muted/10 rounded border border-border/50">
                <div className="flex flex-col items-center text-center">
                    <Droplets className="h-3 w-3 text-blue-400 mb-0.5" />
                    <span className="text-[10px] text-muted-foreground">Moisture</span>
                    <span className="text-xs font-medium text-foreground">{currentConditions.soil_moisture}%</span>
                </div>
                {currentConditions.temperature && (
                    <div className="flex flex-col items-center text-center">
                        <Thermometer className="h-3 w-3 text-orange-400 mb-0.5" />
                        <span className="text-[10px] text-muted-foreground">Temp</span>
                        <span className="text-xs font-medium text-foreground">{Math.round(currentConditions.temperature)}°C</span>
                    </div>
                )}
                {currentConditions.weather_condition && (
                    <div className="flex flex-col items-center text-center">
                        <CloudIcon className="h-3 w-3 text-gray-400 mb-0.5" />
                        <span className="text-[10px] text-muted-foreground">Weather</span>
                        <span className="text-xs font-medium text-foreground capitalize">{currentConditions.weather_condition}</span>
                    </div>
                )}
                {daysSincePlanting && (
                    <div className="flex flex-col items-center text-center">
                        <Clock className="h-3 w-3 text-green-400 mb-0.5" />
                        <span className="text-[10px] text-muted-foreground">Days</span>
                        <span className="text-xs font-medium text-foreground">{daysSincePlanting}</span>
                    </div>
                )}
            </div>

            {/* Recommendations List */}
            <div className="space-y-2 max-h-64 overflow-y-auto">
                {recommendations.map((rec, index) => {
                    const IconComponent = getIconComponent(rec.icon);
                    
                    return (
                        <div 
                            key={index} 
                            className={`p-3 rounded border transition-colors ${
                                rec.priority === 'high' 
                                    ? 'bg-red-50/50 border-red-200/50 dark:bg-red-950/20 dark:border-red-800/30' 
                                    : rec.priority === 'medium'
                                    ? 'bg-yellow-50/50 border-yellow-200/50 dark:bg-yellow-950/20 dark:border-yellow-800/30'
                                    : 'bg-green-50/50 border-green-200/50 dark:bg-green-950/20 dark:border-green-800/30'
                            }`}
                        >
                            <div className="flex items-start gap-3">
                                <div className={`p-1.5 rounded ${
                                    rec.priority === 'high' 
                                        ? 'bg-red-100 dark:bg-red-900/40' 
                                        : rec.priority === 'medium'
                                        ? 'bg-yellow-100 dark:bg-yellow-900/40'
                                        : 'bg-green-100 dark:bg-green-900/40'
                                }`}>
                                    <IconComponent className={`h-3 w-3 ${
                                        rec.priority === 'high' 
                                            ? 'text-red-600 dark:text-red-400' 
                                            : rec.priority === 'medium'
                                            ? 'text-yellow-600 dark:text-yellow-400'
                                            : 'text-green-600 dark:text-green-400'
                                    }`} />
                                </div>
                                
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center justify-between mb-1">
                                        <h4 className="text-xs font-medium text-foreground">{rec.action}</h4>
                                        <div className="flex items-center gap-1">
                                            <Badge className={`text-[10px] px-1 py-0 ${getPriorityColor(rec.priority)}`}>
                                                {rec.priority}
                                            </Badge>
                                        </div>
                                    </div>
                                    
                                    <p className="text-xs text-muted-foreground mb-1">
                                        {rec.description}
                                    </p>
                                    
                                    <div className="flex items-center justify-between">
                                        <span className={`text-[10px] font-medium uppercase tracking-wider ${getCategoryColor(rec.category)}`}>
                                            {rec.category.replace('_', ' ')}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
            
            {recommendations.length > 0 && (
                <div className="pt-2 border-t border-border/30">
                    <p className="text-[10px] text-muted-foreground text-center">
                        Last updated: {new Date(currentConditions.reading_date).toLocaleString()}
                    </p>
                </div>
            )}
        </div>
    );
};

export default CropCareRecommendations;