;(function()
{
	// CommonJS
	SyntaxHighlighter = SyntaxHighlighter || (typeof require !== 'undefined'? require('shCore').SyntaxHighlighter : null);

	function Brush()
	{
		// shBrushObjC.js:
		// Copyright 2012 Philip Wiloughby.
		// Based on shBrushCpp.js:
		// Copyright 2006 Shin, YoungJin
	
		var datatypes =	'' +
// C types
'__complex__ __imag__ __real__ _Bool _Complex _Imaginary __block ' +
'char double float int long ' +
'short signed ' +
'unsigned void ' +
// Objective-C types
'BOOL IBAction ' +
'SEL id ' +
// Foundation framework types
'NSAppleEventManagerSuspensionID NSComparator NSDecimal NSHashEnumerator NSHashTable NSHashTableCallBacks NSHashTableOptions ' +
'NSInteger NSMapEnumerator NSMapTable NSMapTableKeyCallBacks NSMapTableOptions NSMapTableValueCallBacks NSPoint NSPointArray ' +
'NSPointPointer NSRange NSRangePointer NSRect NSRectArray NSRectPointer NSSize NSSizeArray NSSizePointer NSSocketNativeHandle ' +
'NSStringEncoding NSSwappedDouble NSSwappedFloat NSTimeInterval NSUncaughtExceptionHandler NSUInteger NSZone ' +
// Insert further types here
'';

		var keywords = '' +	
// C keywords
'__alignof__ __asm__ __attribute__ __extension__ __label__ ' +
'__restrict __restrict__ __typeof__ asm auto break case ' +
'const continue default do else enum extern for goto if inline ' +
'pascal register restrict return sizeof static struct switch typedef typeof ' +
'union volatile while ' +
// Objective-C keywords
'_cmd __strong __weak ' +
'bycopy byref in inout oneway out IBOutlet IBOutletCollection ' +
'IMP NS_DURING NS_ENDHANDLER NS_HANDLER NS_VALUERETURN NS_VOIDRETURN ' +
'self super';
					
		var functions =	'' +
// Foundation framework functions
// - Assertions
'NSAssert NSAssert1 NSAssert2 NSAssert3 NSAssert4 NSAssert5 NSParameterAssert ' + 
'NSCAssert NSCAssert1 NSCAssert2 NSCAssert3 NSCAssert4 NSCAssert5 NSCParameterAssert ' +
// - Bundles
'NSLocalizedString NSLocalizedStringFromTable NSLocalizedStringFromTableInBundle NSLocalizedStringWithDefaultValue ' +
// - Byte Ordering
'NSConvertHostDoubleToSwapped NSConvertHostFloatToSwapped NSConvertSwappedDoubleToHost NSConvertSwappedFloatToHost ' +
'NSHostByteOrder NSSwapBigDoubleToHost NSSwapBigFloatToHost NSSwapBigIntToHost NSSwapBigLongLongToHost ' +
'NSSwapBigLongToHost NSSwapBigShortToHost NSSwapDouble NSSwapFloat NSSwapHostDoubleToBig NSSwapHostDoubleToLittle ' +
'NSSwapHostFloatToBig NSSwapHostFloatToLittle NSSwapHostIntToBig NSSwapHostIntToLittle NSSwapHostLongLongToBig ' +
'NSSwapHostLongLongToLittle NSSwapHostLongToBig NSSwapHostLongToLittle NSSwapHostShortToBig NSSwapHostShortToLittle ' +
'NSSwapInt NSSwapLittleDoubleToHost NSSwapLittleFloatToHost NSSwapLittleIntToHost NSSwapLittleLongLongToHost ' +
'NSSwapLittleLongToHost NSSwapLittleShortToHost NSSwapLong NSSwapLongLong NSSwapShort ' +
// - Decimals
'NSDecimalAdd NSDecimalCompact NSDecimalCompare NSDecimalCopy NSDecimalDivide NSDecimalIsNotANumber ' +
'NSDecimalMultiply NSDecimalMultiplyByPowerOf10 NSDecimalNormalize NSDecimalPower NSDecimalRound NSDecimalString NSDecimalSubtract ' +
// - Exception Handling
'NS_DURING NS_ENDHANDLER NS_HANDLER NS_VALUERETURN NS_VOIDRETURN ' +
// - Managing Object Allocation and Deallocation
'NSAllocateObject NSCopyObject NSDeallocateObject NSDecrementExtraRefCountWasZero ' +
'NSExtraRefCount NSIncrementExtraRefCount NSShouldRetainWithZone ' +
// - Interacting with the Objective-C Runtime
'NSGetSizeAndAlignment NSClassFromString NSStringFromClass NSSelectorFromString NSStringFromSelector ' +
'NSStringFromProtocol NSProtocolFromString ' +
// - Logging Output
'NSLog NSLogv ' +
// - Managing File Paths
'NSFullUserName NSHomeDirectory NSHomeDirectoryForUser NSOpenStepRootDirectory ' + 
'NSSearchPathForDirectoriesInDomains NSTemporaryDirectory NSUserName ' +
// - Managing Ranges
'NSEqualRanges NSIntersectionRange NSLocationInRange NSMakeRange NSMaxRange ' +
'NSRangeFromString NSStringFromRange NSUnionRange ' +
// - Uncaught Exception Handlers
'NSGetUncaughtExceptionHandler NSSetUncaughtExceptionHandler ' +
// - Managing Memory
'NSDefaultMallocZone NSMakeCollectable NSAllocateMemoryPages NSCopyMemoryPages NSDeallocateMemoryPages ' +
'NSLogPageSize NSPageSize NSRealMemoryAvailable NSRoundDownToMultipleOfPageSize NSRoundUpToMultipleOfPageSize ' +
// - Managing Zones
'NSCreateZone NSRecycleZone NSSetZoneName NSZoneCalloc NSZoneFree NSZoneFromPointer NSZoneMalloc NSZoneName NSZoneRealloc ' +
// Insert further functions here
'';

		var constants = '' +
// C constants
'FALSE NULL TRUE ' +
// Objective-C constants
'NO Nil YES nil ' +
// Foundation framework constants
// - enum _NSByteOrder
'NS_UnknownByteOrder NS_LittleEndian NS_BigEndian ' +
// -
'NSNotFound ' +
// - NSEnumerationOptions
'NSEnumerationConcurrent NSEnumerationReverse ' +
// - NSComparisonResult
'NSOrderedAscending NSOrderedSame NSOrderedDescending ' +
// - NSSortOptions
'NSSortConcurrent NSSortStable ' +
// - NSSearchPathDirectory
'NSApplicationDirectory NSDemoApplicationDirectory NSDeveloperApplicationDirectory NSAdminApplicationDirectory ' +
'NSLibraryDirectory NSDeveloperDirectory NSUserDirectory NSDocumentationDirectory NSDocumentDirectory ' +
'NSCoreServiceDirectory NSAutosavedInformationDirectory NSDesktopDirectory NSCachesDirectory NSApplicationSupportDirectory ' +
'NSDownloadsDirectory NSInputMethodsDirectory NSMoviesDirectory NSMusicDirectory NSPicturesDirectory NSPrinterDescriptionDirectory ' +
'NSSharedPublicDirectory NSPreferencePanesDirectory NSItemReplacementDirectory NSAllApplicationsDirectory NSAllLibrariesDirectory' + 
// - NSError Codes
'NSFileNoSuchFileError NSFileLockingError NSFileReadUnknownError NSFileReadNoPermissionError NSFileReadInvalidFileNameError ' +
'NSFileReadCorruptFileError NSFileReadNoSuchFileError NSFileReadInapplicableStringEncodingError NSFileReadUnsupportedSchemeError ' +
'NSFileReadTooLargeError NSFileReadUnknownStringEncodingError NSFileWriteUnknownError NSFileWriteNoPermissionError ' +
'NSFileWriteInvalidFileNameError NSFileWriteFileExistsError NSFileWriteInapplicableStringEncodingError NSFileWriteUnsupportedSchemeError ' +
'NSFileWriteOutOfSpaceError NSFileWriteVolumeReadOnlyError NSKeyValueValidationError NSFormattingError NSUserCancelledError ' +
'NSFileErrorMinimum NSFileErrorMaximum NSValidationErrorMinimum NSValidationErrorMaximum NSFormattingErrorMinimum NSFormattingErrorMaximum ' +
'NSPropertyListReadCorruptError NSPropertyListReadUnknownVersionError NSPropertyListReadStreamError NSPropertyListWriteStreamError ' +
'NSPropertyListErrorMinimum NSPropertyListErrorMaximum NSExecutableErrorMinimum NSExecutableNotLoadableError NSExecutableArchitectureMismatchError ' +
'NSExecutableRuntimeMismatchError NSExecutableLoadError NSExecutableLinkError NSExecutableErrorMaximum ' +
// - URL Loading Error Codes
'NSURLErrorUnknown NSURLErrorCancelled NSURLErrorBadURL NSURLErrorTimedOut NSURLErrorUnsupportedURL NSURLErrorCannotFindHost ' +
'NSURLErrorCannotConnectToHost NSURLErrorDataLengthExceedsMaximum NSURLErrorNetworkConnectionLost NSURLErrorDNSLookupFailed ' +
'NSURLErrorHTTPTooManyRedirects NSURLErrorResourceUnavailable NSURLErrorNotConnectedToInternet NSURLErrorRedirectToNonExistentLocation ' +
'NSURLErrorBadServerResponse NSURLErrorUserCancelledAuthentication NSURLErrorUserAuthenticationRequired NSURLErrorZeroByteResource ' +
'NSURLErrorCannotDecodeRawData NSURLErrorCannotDecodeContentData NSURLErrorCannotParseResponse NSURLErrorInternationalRoamingOff ' +
'NSURLErrorCallIsActive NSURLErrorDataNotAllowed NSURLErrorRequestBodyStreamExhausted NSURLErrorFileDoesNotExist NSURLErrorFileIsDirectory ' +
'NSURLErrorNoPermissionsToReadFile NSURLErrorSecureConnectionFailed NSURLErrorServerCertificateHasBadDate NSURLErrorServerCertificateUntrusted ' +
'NSURLErrorServerCertificateHasUnknownRoot NSURLErrorServerCertificateNotYetValid NSURLErrorClientCertificateRejected ' +
'NSURLErrorClientCertificateRequired NSURLErrorCannotLoadFromNetwork NSURLErrorCannotCreateFile NSURLErrorCannotOpenFile ' +
'NSURLErrorCannotCloseFile NSURLErrorCannotWriteToFile NSURLErrorCannotRemoveFile NSURLErrorCannotMoveFile ' +
'NSURLErrorDownloadDecodingFailedMidStream NSURLErrorDownloadDecodingFailedToComplete ' +
// - Global objects
'NSCocoaErrorDomain NSURLErrorDomain ' +
// - Limits
'NSDecimalMaxSize NSDecimalNoScale NSIntegerMax NSIntegerMin NSUIntegerMax ' +
// - Exceptions
'NSGenericException NSRangeException NSInvalidArgumentException NSInternalInconsistencyException NSMallocException ' +
'NSObjectInaccessibleException NSObjectNotAvailableException NSDestinationInvalidException NSPortTimeoutException ' +
'NSInvalidSendPortException NSInvalidReceivePortException NSPortSendException NSPortReceiveException NSOldStyleException ' +
// - Version number
'NSFoundationVersionNumber ' +
'NSFoundationVersionNumber10_0 NSFoundationVersionNumber10_1 NSFoundationVersionNumber10_1_1 NSFoundationVersionNumber10_1_2 ' +
'NSFoundationVersionNumber10_1_3 NSFoundationVersionNumber10_1_4 NSFoundationVersionNumber10_2 NSFoundationVersionNumber10_2_1 ' +
'NSFoundationVersionNumber10_2_2 NSFoundationVersionNumber10_2_3 NSFoundationVersionNumber10_2_4 NSFoundationVersionNumber10_2_5 ' +
'NSFoundationVersionNumber10_2_6 NSFoundationVersionNumber10_2_7 NSFoundationVersionNumber10_2_8 NSFoundationVersionNumber10_3 ' +
'NSFoundationVersionNumber10_3_1 NSFoundationVersionNumber10_3_2 NSFoundationVersionNumber10_3_3 NSFoundationVersionNumber10_3_4 ' +
'NSFoundationVersionNumber10_3_5 NSFoundationVersionNumber10_3_6 NSFoundationVersionNumber10_3_7 NSFoundationVersionNumber10_3_8 ' +
'NSFoundationVersionNumber10_3_9 NSFoundationVersionNumber10_4 NSFoundationVersionNumber10_4_1 NSFoundationVersionNumber10_4_2 ' +
'NSFoundationVersionNumber10_4_3 NSFoundationVersionNumber10_4_4_Intel NSFoundationVersionNumber10_4_4_PowerPC ' +
'NSFoundationVersionNumber10_4_5 NSFoundationVersionNumber10_4_6 NSFoundationVersionNumber10_4_7 NSFoundationVersionNumber10_4_8 ' +
'NSFoundationVersionNumber10_4_9 NSFoundationVersionNumber10_4_10 NSFoundationVersionNumber10_4_11 NSFoundationVersionNumber10_5 ' +
'NSFoundationVersionNumber10_5_1 NSFoundationVersionNumber10_5_2 NSFoundationVersionNumber10_5_3 NSFoundationVersionNumber10_5_4 ' +
'NSFoundationVersionNumber10_5_5 NSFoundationVersionNumber10_5_6 NSFoundationVersionNumber_iOS_2_0 NSFoundationVersionNumber_iOS_2_1 ' +
'NSFoundationVersionNumber_iOS_2_2 ' +

// Insert further constants here
'';

var classNames = '' +
// Foundation framework classes
'NSAffineTransform NSAppleEventDescriptor NSAppleEventManager NSAppleScript ' +
'NSArchiver NSArray NSAssertionHandler NSAttributedString NSAutoreleasePool ' +
'NSBlockOperation NSBundle NSCache NSCachedURLResponse NSCalendar NSCharacterSet ' +
'NSClassDescription NSCloneCommand NSCloseCommand NSCoder NSComparisonPredicate ' +
'NSCompoundPredicate NSCondition NSConditionLock NSConnection NSCountCommand ' +
'NSCountedSet NSCreateCommand NSData NSDataDetector NSDate NSDateComponents ' +
'NSDateFormatter NSDecimalNumber NSDecimalNumberHandler NSDeleteCommand ' +
'NSDeserializer NSDictionary NSDirectoryEnumerator NSDistantObject ' +
'NSDistantObjectRequest NSDistributedLock NSDistributedNotificationCenter ' +
'NSEnumerator NSError NSException NSExistsCommand NSExpression NSFileCoordinator ' +
'NSFileHandle NSFileManager NSFileWrapper NSFormatter NSGarbageCollector ' +
'NSGetCommand NSHashTable NSHost NSHTTPCookie NSHTTPCookieStorage ' +
'NSHTTPURLResponse NSIndexPath NSIndexSet NSIndexSpecifier NSInputStream ' +
'NSInvocation NSInvocationOperation NSKeyedArchiver NSKeyedUnarchiver ' +
'NSLinguisticTagger NSLocale NSLock NSLogicalTest NSMachBootstrapServer ' +
'NSMachPort NSMapTable NSMessagePort NSMessagePortNameServer NSMetadataItem ' +
'NSMetadataQuery NSMetadataQueryAttributeValueTuple NSMetadataQueryResultGroup ' +
'NSMethodSignature NSMiddleSpecifier NSMoveCommand NSMutableArray ' +
'NSMutableAttributedString NSMutableCharacterSet NSMutableData ' +
'NSMutableDictionary NSMutableIndexSet NSMutableOrderedSet NSMutableSet ' +
'NSMutableString NSMutableURLRequest NSNameSpecifier NSNetService ' +
'NSNetServiceBrowser NSNotification NSNotificationCenter NSNotificationQueue ' +
'NSNull NSNumber NSNumberFormatter NSObject NSOperation NSOperationQueue ' +
'NSOrderedSet NSOrthography NSOutputStream NSPipe NSPointerArray ' +
'NSPointerFunctions NSPort NSPortCoder NSPortMessage NSPortNameServer ' +
'NSPositionalSpecifier NSPredicate NSProcessInfo NSPropertyListSerialization ' +
'NSPropertySpecifier NSProtocolChecker NSProxy NSQuitCommand NSRandomSpecifier ' +
'NSRangeSpecifier NSRecursiveLock NSRegularExpression NSRelativeSpecifier ' +
'NSRunLoop NSScanner NSScriptClassDescription NSScriptCoercionHandler ' +
'NSScriptCommand NSScriptCommandDescription NSScriptExecutionContext ' +
'NSScriptObjectSpecifier NSScriptSuiteRegistry NSScriptWhoseTest NSSerializer ' +
'NSSet NSSetCommand NSSocketPort NSSocketPortNameServer NSSortDescriptor ' +
'NSSpecifierTest NSSpellServer NSStream NSString NSTask NSTextCheckingResult ' +
'NSThread NSTimer NSTimeZone NSUbiquitousKeyValueStore NSUnarchiver ' +
'NSUndoManager NSUniqueIDSpecifier NSURL NSURLAuthenticationChallenge NSURLCache ' +
'NSURLConnection NSURLCredential NSURLCredentialStorage NSURLDownload ' +
'NSURLHandle NSURLProtectionSpace NSURLProtocol NSURLRequest NSURLResponse ' +
'NSUserDefaults NSValue NSValueTransformer NSWhoseSpecifier NSXMLDocument ' +
'NSXMLDTD NSXMLDTDNode NSXMLElement NSXMLNode NSXMLParser ' +
// Insert further classes here
'';

var protocolNames = '' +
// Foundation framework protocols
'NSCoding NSComparisonMethods NSConnectionDelegate NSCopying ' +
'NSDecimalNumberBehaviors NSErrorRecoveryAttempting NSFastEnumeration ' +
'NSFileManagerDelegate NSFilePresenter NSKeyedArchiverDelegate ' +
'NSKeyedUnarchiverDelegate NSKeyValueCoding NSKeyValueObserving NSLocking ' +
'NSMachPortDelegate NSMetadataQueryDelegate NSMutableCopying ' +
'NSNetServiceBrowserDelegate NSNetServiceDelegate ' +
'NSObjCTypeSerializationCallBack NSObject NSPortDelegate ' +
'NSScriptingComparisonMethods NSScriptKeyValueCoding NSScriptObjectSpecifiers ' +
'NSSpellServerDelegate NSStreamDelegate NSURLAuthenticationChallengeSender ' +
'NSURLConnectionDelegate NSURLHandleClient NSURLProtocolClient ' +
'NSXMLParserDelegate ' +

// Insert further protocols here
'';
		this.regexList = [
			{ regex: SyntaxHighlighter.regexLib.singleLineCComments,	css: 'comments' },			// one line comments
			{ regex: SyntaxHighlighter.regexLib.multiLineCComments,		css: 'comments' },			// multiline comments
			{ regex: SyntaxHighlighter.regexLib.doubleQuotedString,		css: 'string' },			// strings
			{ regex: SyntaxHighlighter.regexLib.singleQuotedString,		css: 'string' },			// strings
			{ regex: /^ *#.*/gm,										css: 'preprocessor' },
			{ regex: new RegExp(this.getKeywords(datatypes), 'gm'),		css: 'color1' },
			{ regex: new RegExp(this.getKeywords(functions), 'gm'),		css: 'functions' },
			{ regex: new RegExp(this.getKeywords(keywords), 'gm'),		css: 'keyword' },
			{ regex: new RegExp(this.getKeywords(constants), 'gm'),		css: 'constants' },
			{ regex: new RegExp(this.getKeywords(classNames), 'gm'),	css: 'color2' },
			{ regex: new RegExp(this.getKeywords(protocolNames), 'gm'),	css: 'color3' },
			{ regex: /\@(?:catch|class|defs|dynamic|encode|end|finally|implementation|interface|optional|package|private|property|protected|protocol|public|required|selector|synchronized|synthesize|throw|try)(?=\b)/gm, css: 'keyword' }
			];
	};

	Brush.prototype	= new SyntaxHighlighter.Highlighter();
	Brush.aliases	= ['objective-c', 'objc', 'cocoa'];

	SyntaxHighlighter.brushes.Cpp = Brush;

	// CommonJS
	typeof(exports) != 'undefined' ? exports.Brush = Brush : null;
})();
