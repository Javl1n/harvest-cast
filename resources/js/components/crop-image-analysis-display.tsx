import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { CropImage } from "@/types";
import { router } from "@inertiajs/react";
import axios from "axios";
import { AlertTriangle, CheckCircle, Loader2, Trash2, XCircle } from "lucide-react";
import { useState } from "react";

interface CropImageAnalysisDisplayProps {
    image: CropImage;
    isAdmin: boolean;
}

const CropImageAnalysisDisplay = ({ image, isAdmin }: CropImageAnalysisDisplayProps) => {
    const [deleting, setDeleting] = useState(false);

    const handleDelete = async () => {
        if (!confirm('Are you sure you want to delete this image? This action cannot be undone.')) {
            return;
        }

        setDeleting(true);

        try {
            await axios.delete(`/crop-images/${image.id}`);

            // Success - refresh the page to remove the deleted image
            router.reload({ only: ['latestCropImage'] });
        } catch (err: any) {
            setDeleting(false);

            // Handle errors
            if (err.response?.status === 403) {
                alert('You do not have permission to delete this image.');
            } else if (err.response?.data?.message) {
                alert(err.response.data.message);
            } else {
                alert('Failed to delete image. Please try again.');
            }

            console.error('Delete error:', err);
        }
    };

    const getHealthBadge = () => {
        if (!image.processed) {
            return (
                <Badge className="bg-blue-500/20 text-blue-400 border-blue-500/30">
                    <Loader2 className="h-3 w-3 mr-1 animate-spin" />
                    Analyzing...
                </Badge>
            );
        }

        const status = image.ai_analysis?.health_status || image.health_status;

        switch (status) {
            case 'healthy':
                return (
                    <Badge className="bg-green-500/20 text-green-400 border-green-500/30">
                        <CheckCircle className="h-3 w-3 mr-1" />
                        Healthy
                    </Badge>
                );
            case 'warning':
                return (
                    <Badge className="bg-yellow-500/20 text-yellow-400 border-yellow-500/30">
                        <AlertTriangle className="h-3 w-3 mr-1" />
                        Warning
                    </Badge>
                );
            case 'diseased':
                return (
                    <Badge className="bg-red-500/20 text-red-400 border-red-500/30">
                        <XCircle className="h-3 w-3 mr-1" />
                        Diseased
                    </Badge>
                );
            case 'error':
                return (
                    <Badge className="bg-gray-500/20 text-gray-400 border-gray-500/30">
                        Analysis Failed
                    </Badge>
                );
            default:
                return null;
        }
    };

    const imageDate = new Date(image.image_date);
    const createdAt = new Date(image.created_at);

    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <h4 className="text-sm font-medium">Today's Crop Health</h4>
                    {getHealthBadge()}
                </div>
                {isAdmin && (
                    <Button
                        onClick={handleDelete}
                        disabled={deleting}
                        variant="ghost"
                        size="sm"
                        className="h-6 w-6 p-0 text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20"
                    >
                        {deleting ? (
                            <Loader2 className="h-3 w-3 animate-spin" />
                        ) : (
                            <Trash2 className="h-3 w-3" />
                        )}
                    </Button>
                )}
            </div>

            <div className="relative">
                <img
                    src={image.image_url}
                    alt={`Crop image from ${imageDate.toLocaleDateString()}`}
                    className="w-full h-48 object-cover rounded-lg border border-border"
                />
                <div className="absolute bottom-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded">
                    {createdAt.toLocaleTimeString()}
                </div>
            </div>

            {image.processed && image.ai_analysis && (
                <div className="space-y-2">
                    {image.ai_analysis.diseases && image.ai_analysis.diseases.length > 0 && (
                        <div className="p-2 bg-red-50 border border-red-200 rounded dark:bg-red-950/20 dark:border-red-800/30">
                            <p className="text-xs font-medium text-red-900 dark:text-red-300 mb-1">
                                Detected Issues:
                            </p>
                            <ul className="text-xs text-red-700 dark:text-red-400 list-disc list-inside">
                                {image.ai_analysis.diseases.map((disease, idx) => (
                                    <li key={idx} className="capitalize">
                                        {disease.replace(/_/g, ' ')}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {image.ai_analysis.recommendations && image.ai_analysis.recommendations.length > 0 && (
                        <div className="p-2 bg-blue-50 border border-blue-200 rounded dark:bg-blue-950/20 dark:border-blue-800/30">
                            <p className="text-xs font-medium text-blue-900 dark:text-blue-300 mb-1">
                                AI Recommendations:
                            </p>
                            <ul className="text-xs text-blue-700 dark:text-blue-400 space-y-1">
                                {image.ai_analysis.recommendations.map((rec, idx) => (
                                    <li key={idx} className="flex items-start">
                                        <span className="mr-1">•</span>
                                        <span>{rec}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {image.ai_analysis.confidence && (
                        <p className="text-xs text-muted-foreground text-center">
                            Confidence: {(image.ai_analysis.confidence * 100).toFixed(0)}%
                        </p>
                    )}
                </div>
            )}

            {!image.processed && (
                <div className="p-3 bg-blue-50 border border-blue-200 rounded text-center dark:bg-blue-950/20 dark:border-blue-800/30">
                    <Loader2 className="h-5 w-5 animate-spin mx-auto mb-2 text-blue-600 dark:text-blue-400" />
                    <p className="text-xs text-blue-700 dark:text-blue-400">
                        AI is analyzing your crop image...
                    </p>
                    <p className="text-xs text-blue-600 dark:text-blue-500 mt-1">
                        This may take a few moments. Refresh the page to see results.
                    </p>
                </div>
            )}
        </div>
    );
};

export default CropImageAnalysisDisplay;
