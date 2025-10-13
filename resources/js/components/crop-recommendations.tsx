import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "@/components/ui/collapsible";
import { Separator } from "@/components/ui/separator";
import { CropRecommendation, CurrentConditions, SensorInterface } from "@/types";
import { ChevronDown, Droplets, Star, Thermometer, CloudIcon, RefreshCw } from "lucide-react";
import { useState } from "react";
import axios from "axios";
import { useEchoPublic } from "@laravel/echo-react";

interface CropRecommendationsProps {
    initialRecommendations: CropRecommendation[];
    currentConditions: CurrentConditions | null;
    sensorId: string;
    onRecommendationSelect?: (crop: string, commodityId: number, harvestDays: number) => void;
    selectedCrop?: string;
    commodities?: Array<{id: number, name: string}>;
}

const getSuitabilityColor = (suitability: string) => {
    switch (suitability) {
        case 'excellent':
            return 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30';
        case 'good':
            return 'bg-blue-500/20 text-blue-400 border-blue-500/30';
        case 'fair':
            return 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30';
        case 'poor':
            return 'bg-orange-500/20 text-orange-400 border-orange-500/30';
        case 'unsuitable':
            return 'bg-red-500/20 text-red-400 border-red-500/30';
        default:
            return 'bg-gray-500/20 text-gray-400 border-gray-500/30';
    }
};

const CropRecommendations = ({ 
    initialRecommendations, 
    currentConditions, 
    sensorId, 
    onRecommendationSelect,
    selectedCrop,
    commodities = []
}: CropRecommendationsProps) => {
    const [recommendations, setRecommendations] = useState<CropRecommendation[]>(initialRecommendations);
    const [conditions, setConditions] = useState<CurrentConditions | null>(currentConditions);
    const [loading, setLoading] = useState(false);
    const [expandedCards, setExpandedCards] = useState<string[]>([]);

    const refreshRecommendations = async () => {
        setLoading(true);
        try {
            const response = await axios.get(`/api/crops/recommendations/${sensorId}`);
            if (response.data.success) {
                setRecommendations(response.data.recommendations);
                setConditions(response.data.currentConditions);
            }
        } catch (error) {
            console.error('Failed to refresh recommendations:', error);
        } finally {
            setLoading(false);
        }
    };

    // Listen to sensor updates via useEchoPublic hook
    useEchoPublic('sensors', 'SensorUpdated', ({ sensor }: { sensor: SensorInterface }) => {
        // Only refresh if this is the sensor we're monitoring
        if (sensor.id === sensorId) {
            console.log('Sensor updated, refreshing recommendations...', sensor);
            refreshRecommendations();
        }
    });

    const toggleExpanded = (crop: string) => {
        setExpandedCards(prev => 
            prev.includes(crop) 
                ? prev.filter(c => c !== crop)
                : [...prev, crop]
        );
    };

    const handleSelectCrop = (crop: string, harvestDays: number) => {
        if (onRecommendationSelect) {
            // Find the commodity ID for the selected crop
            const commodity = commodities.find(c => c.name.toLowerCase() === crop.toLowerCase());
            if (commodity) {
                onRecommendationSelect(crop, commodity.id, harvestDays);
            }
        }
    };

    if (!conditions || recommendations.length === 0) {
        return (
            <div className="space-y-3">
                <h2 className="text-sm font-medium text-muted-foreground flex items-center gap-2 uppercase tracking-wider">
                    <Star className="h-4 w-4" />
                    Crop Recommendations
                </h2>
                <div className="p-4 bg-muted/10 rounded-lg border border-border/50">
                    <p className="text-sm text-muted-foreground">
                        {!conditions 
                            ? "No sensor readings available for recommendations."
                            : "No recommendations available at this time."
                        }
                    </p>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h2 className="text-sm font-medium text-muted-foreground flex items-center gap-2 uppercase tracking-wider">
                    <Star className="h-4 w-4" />
                    Recommendations
                </h2>
                <Button 
                    type="button"
                    variant="ghost" 
                    size="sm" 
                    onClick={refreshRecommendations}
                    disabled={loading}
                    className="h-8 w-8 p-0"
                >
                    <RefreshCw className={`h-3 w-3 ${loading ? 'animate-spin' : ''}`} />
                </Button>
            </div>
            
            {/* Current Conditions - Compact */}
            <div className="grid grid-cols-3 gap-2 p-3 bg-muted/10 rounded-lg border border-border/50">
                <div className="flex flex-col items-center text-center">
                    <Droplets className="h-4 w-4 text-blue-400 mb-1" />
                    <span className="text-xs text-muted-foreground">Soil</span>
                    <span className="text-sm font-medium text-foreground">{conditions.soil_moisture}%</span>
                </div>
                {conditions.temperature && (
                    <div className="flex flex-col items-center text-center">
                        <Thermometer className="h-4 w-4 text-orange-400 mb-1" />
                        <span className="text-xs text-muted-foreground">Temp</span>
                        <span className="text-sm font-medium text-foreground">{Math.round(conditions.temperature)}°C</span>
                    </div>
                )}
                {conditions.weather_condition && (
                    <div className="flex flex-col items-center text-center">
                        <CloudIcon className="h-4 w-4 text-gray-400 mb-1" />
                        <span className="text-xs text-muted-foreground">Weather</span>
                        <span className="text-sm font-medium text-foreground">{conditions.weather_condition}</span>
                    </div>
                )}
            </div>

            {/* Recommendations - Compact Cards */}
            <div className="space-y-2">
                {recommendations.map((rec, index) => (
                    <div key={`${rec.crop}-${index}`} className={`p-3 rounded-lg border transition-colors ${
                        selectedCrop === rec.crop 
                            ? 'bg-primary/10 border-primary/30' 
                            : 'bg-muted/10 border-border/50 hover:bg-muted/20'
                    }`}>
                        <div className="flex items-center justify-between mb-2">
                            <div>
                                <h3 className="text-sm font-medium text-foreground">{rec.crop}</h3>
                                <p className="text-xs text-muted-foreground">{rec.variety}</p>
                            </div>
                            <div className="flex items-center gap-2">
                                <Badge className={`text-xs ${getSuitabilityColor(rec.suitability)}`}>
                                    {rec.score}
                                </Badge>
                                {onRecommendationSelect && (
                                    <Button 
                                        type="button"
                                        size="sm" 
                                        variant={selectedCrop === rec.crop ? "default" : "outline"}
                                        onClick={() => handleSelectCrop(rec.crop, rec.harvest_days)}
                                        className="h-6 px-2 text-xs"
                                    >
                                        {selectedCrop === rec.crop ? 'Selected' : 'Select'}
                                    </Button>
                                )}
                            </div>
                        </div>

                        {/* Key Reasons - Compact */}
                        <div className="space-y-1 mb-2">
                            {rec.reasons.slice(0, 2).map((reason, i) => (
                                <div key={i} className="text-xs text-muted-foreground flex items-center gap-2">
                                    <div className="w-1 h-1 bg-emerald-400 rounded-full flex-shrink-0" />
                                    {reason}
                                </div>
                            ))}
                        </div>

                        {/* Expandable Details */}
                        <Collapsible 
                            open={expandedCards.includes(rec.crop)}
                            onOpenChange={() => toggleExpanded(rec.crop)}
                        >
                            <CollapsibleTrigger asChild>
                                <Button type="button" variant="ghost" size="sm" className="w-full justify-between p-0 h-auto text-xs">
                                    <span className="text-muted-foreground">More details</span>
                                    <ChevronDown className={`h-3 w-3 transition-transform ${
                                        expandedCards.includes(rec.crop) ? 'rotate-180' : ''
                                    }`} />
                                </Button>
                            </CollapsibleTrigger>
                            <CollapsibleContent className="space-y-2 mt-2 pt-2 border-t border-border/30">
                                <div className="space-y-2 text-xs">
                                    <div>
                                        <span className="font-medium text-foreground">Harvest:</span>
                                        <p className="text-muted-foreground mt-1">{rec.harvest_time}</p>
                                    </div>
                                    <div>
                                        <span className="font-medium text-foreground">Water:</span>
                                        <p className="text-muted-foreground mt-1">{rec.water_requirements}</p>
                                    </div>
                                    <div>
                                        <span className="font-medium text-foreground">Tips:</span>
                                        <p className="text-muted-foreground mt-1">{rec.planting_tips}</p>
                                    </div>
                                    {rec.reasons.length > 2 && (
                                        <div>
                                            <span className="font-medium text-foreground">Additional factors:</span>
                                            <div className="space-y-1 mt-1">
                                                {rec.reasons.slice(2).map((reason, i) => (
                                                    <div key={i} className="text-muted-foreground flex items-center gap-2">
                                                        <div className="w-1 h-1 bg-emerald-400 rounded-full flex-shrink-0" />
                                                        {reason}
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </CollapsibleContent>
                        </Collapsible>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default CropRecommendations;