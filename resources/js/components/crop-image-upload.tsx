import { Button } from "@/components/ui/button";
import { router } from "@inertiajs/react";
import axios from "axios";
import { Camera, Upload, Loader2 } from "lucide-react";
import { useRef, useState } from "react";

interface CropImageUploadProps {
    scheduleId: number;
    isAdmin: boolean;
}

const CropImageUpload = ({ scheduleId, isAdmin }: CropImageUploadProps) => {
    const [uploading, setUploading] = useState(false);
    const [preview, setPreview] = useState<string | null>(null);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [cameraActive, setCameraActive] = useState(false);
    const videoRef = useRef<HTMLVideoElement>(null);
    const streamRef = useRef<MediaStream | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const startCamera = async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' }
            });
            if (videoRef.current) {
                videoRef.current.srcObject = stream;
                streamRef.current = stream;
                setCameraActive(true);
                setError(null);
            }
        } catch (err) {
            setError('Unable to access camera. Please use file upload instead.');
            console.error('Camera access error:', err);
        }
    };

    const stopCamera = () => {
        if (streamRef.current) {
            streamRef.current.getTracks().forEach(track => track.stop());
            streamRef.current = null;
        }
        setCameraActive(false);
        if (videoRef.current) {
            videoRef.current.srcObject = null;
        }
    };

    const capturePhoto = () => {
        if (!videoRef.current) {
            return;
        }

        const canvas = document.createElement('canvas');
        canvas.width = videoRef.current.videoWidth;
        canvas.height = videoRef.current.videoHeight;
        const ctx = canvas.getContext('2d');

        if (ctx) {
            ctx.drawImage(videoRef.current, 0, 0);
            canvas.toBlob((blob) => {
                if (blob) {
                    const file = new File([blob], `crop-${Date.now()}.jpg`, { type: 'image/jpeg' });
                    setSelectedFile(file);
                    setPreview(URL.createObjectURL(blob));
                    stopCamera();
                }
            }, 'image/jpeg', 0.95);
        }
    };

    const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            if (!file.type.startsWith('image/')) {
                setError('Please select an image file.');
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                setError('Image must be less than 10MB.');
                return;
            }
            setSelectedFile(file);
            setPreview(URL.createObjectURL(file));
            setError(null);
        }
    };

    const handleUpload = async () => {
        if (!selectedFile) {
            return;
        }

        setUploading(true);
        setError(null);

        const formData = new FormData();
        formData.append('image', selectedFile);
        formData.append('schedule_id', scheduleId.toString());

        try {
            const response = await axios.post('/crop-images', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });

            // Success - image uploaded and analysis started
            setPreview(null);
            setSelectedFile(null);
            setUploading(false);

            // Refresh the page to show the new image
            router.reload({ only: ['latestCropImage'] });
        } catch (err: any) {
            setUploading(false);

            // Handle validation errors from Laravel
            if (err.response?.status === 422 && err.response?.data?.errors) {
                const validationErrors = err.response.data.errors;
                setError(validationErrors.image?.[0] || validationErrors.schedule_id?.[0] || 'Validation failed.');
            } else if (err.response?.status === 403) {
                setError('You do not have permission to upload images.');
            } else if (err.response?.data?.message) {
                setError(err.response.data.message);
            } else {
                setError('Failed to upload image. Please try again.');
            }

            console.error('Upload error:', err);
        }
    };

    const cancelUpload = () => {
        setPreview(null);
        setSelectedFile(null);
        setError(null);
        stopCamera();
    };

    if (!isAdmin) {
        return null;
    }

    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between">
                <h4 className="text-sm font-medium">Crop Health Analysis</h4>
                <p className="text-xs text-muted-foreground">1 image per day</p>
            </div>

            {error && (
                <div className="p-2 bg-red-50 border border-red-200 rounded text-xs text-red-600 dark:bg-red-950/20 dark:border-red-800/30 dark:text-red-400">
                    {error}
                </div>
            )}

            {preview ? (
                <div className="space-y-3">
                    <img
                        src={preview}
                        alt="Preview"
                        className="w-full h-48 object-cover rounded-lg border border-border"
                    />
                    <div className="flex gap-2">
                        <Button
                            onClick={handleUpload}
                            disabled={uploading}
                            className="flex-1"
                            size="sm"
                        >
                            {uploading ? (
                                <>
                                    <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                    Uploading...
                                </>
                            ) : (
                                <>
                                    <Upload className="h-4 w-4 mr-2" />
                                    Upload & Analyze
                                </>
                            )}
                        </Button>
                        <Button
                            onClick={cancelUpload}
                            disabled={uploading}
                            variant="outline"
                            size="sm"
                        >
                            Cancel
                        </Button>
                    </div>
                </div>
            ) : cameraActive ? (
                <div className="space-y-3">
                    <video
                        ref={videoRef}
                        autoPlay
                        playsInline
                        className="w-full h-48 object-cover rounded-lg border border-border bg-black"
                    />
                    <div className="flex gap-2">
                        <Button
                            onClick={capturePhoto}
                            className="flex-1"
                            size="sm"
                        >
                            <Camera className="h-4 w-4 mr-2" />
                            Capture Photo
                        </Button>
                        <Button
                            onClick={stopCamera}
                            variant="outline"
                            size="sm"
                        >
                            Cancel
                        </Button>
                    </div>
                </div>
            ) : (
                <div className="flex gap-2">
                    <Button
                        onClick={startCamera}
                        variant="outline"
                        className="flex-1"
                        size="sm"
                    >
                        <Camera className="h-4 w-4 mr-2" />
                        Take Photo
                    </Button>
                    <Button
                        onClick={() => fileInputRef.current?.click()}
                        variant="outline"
                        className="flex-1"
                        size="sm"
                    >
                        <Upload className="h-4 w-4 mr-2" />
                        Upload Image
                    </Button>
                    <input
                        ref={fileInputRef}
                        type="file"
                        accept="image/*"
                        onChange={handleFileSelect}
                        className="hidden"
                    />
                </div>
            )}
        </div>
    );
};

export default CropImageUpload;
